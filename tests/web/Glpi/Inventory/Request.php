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

use GuzzleHttp;

class Request extends \GLPITestCase {
   private $http_client;
   private $base_uri;

   public function beforeTestMethod($method) {
      global $CFG_GLPI;

      $this->http_client = new GuzzleHttp\Client();
      $this->base_uri    = trim($CFG_GLPI['url_base'], "/")."/";

      parent::beforeTestMethod($method);
   }

   /**
    * Check a response
    *
    * @param Response $res   Request response
    * @param string   $reply Reply tag contents
    *
    * @return void
    */
   private function checkResponse (GuzzleHttp\Psr7\Response $res, $reply) {
      $this->integer($res->getStatusCode())->isIdenticalTo(200);
      $this->string($res->getHeader('content-type')[0])->isIdenticalTo('application/xml');
      $this->string((string)$res->getBody())
         ->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY>$reply</REPLY>\n");

   }

   public function testWrongHttpMethod() {
      $res = $this->http_client->request(
         'GET',
         $this->base_uri . 'front/inventory.php',
         [
            'headers' => [
               'Content-Type' => 'application/xml'
            ]
         ]
      );
      $this->checkResponse($res, '<ERROR>Method not allowed</ERROR>');
   }

   public function testRequestInvalidContent() {
      $res = $this->http_client->request(
         'POST',
         $this->base_uri . 'front/inventory.php',
         [
            'headers' => [
               'Content-Type' => 'application/xml'
            ]
         ]
      );
      $this->checkResponse($res, '<ERROR>XML not well formed!</ERROR>');
   }

   public function testPrologRequest() {
      $res = $this->http_client->request(
         'POST',
         $this->base_uri . 'front/inventory.php',
         [
            'headers' => [
               'Content-Type' => 'application/xml'
            ],
            'body'   => '<?xml version="1.0" encoding="UTF-8" ?>' .
               '<REQUEST>' .
                  '<DEVICEID>mydeviceuniqueid</DEVICEID>' .
                  '<QUERY>PROLOG</QUERY>' .
               '</REQUEST>'
         ]
      );
      $this->integer($res->getStatusCode())->isIdenticalTo(200);
      $this->string($res->getHeader('content-type')[0])->isIdenticalTo('application/xml');
      $this->string((string)$res->getBody())
         ->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY><PROLOG_FREQ>24</PROLOG_FREQ><RESPONSE>SEND</RESPONSE></REPLY>\n");
   }
}
