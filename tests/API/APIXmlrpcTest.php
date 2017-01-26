<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/* Test for inc/api.class.php */

use GuzzleHttp\Exception\ClientException;

class APIXmlrpcTest extends APIBaseClass {
   protected $http_client;
   protected $base_uri = "";

   public function __construct() {
      global $CFG_GLPI;

      $this->http_client = new GuzzleHttp\Client();
      $this->base_uri    = trim($CFG_GLPI['url_base'], "/")."/apixmlrpc.php";
   }

   protected function doHttpRequest($resource = "", $params = []) {
      $headers = array("Content-Type" => "text/xml");
      $request = xmlrpc_encode_request($resource, $params);
      return $this->http_client->post($this->base_uri, ['body'    => $request,
                                                       'headers' => $headers]);
   }

   protected function query($resource = "", $params = [], $expected_code = 200) {
      //reconstruct params for xmlrpc (base params done for rest)
      $flat_params = array_merge($params,
                                 isset($params['query'])   ? $params['query']   : [],
                                 isset($params['headers']) ? $params['headers'] : [],
                                 isset($params['json'])    ? $params['json']    : []);
      unset($flat_params['query'],
            $flat_params['json'],
            $flat_params['verb']);
      if (isset($flat_params['Session-Token'])) {
         $flat_params['session_token'] = $flat_params['Session-Token'];
      }
      // launch query
      try {
         $res = $this->doHttpRequest($resource, $flat_params);
      } catch (Exception $e) {
         $response = $e->getResponse();
         $this->assertEquals($expected_code, $response->getStatusCode());
         return;
      }
      // common tests
      if (isset($this->last_error)) {
         $this->assertNotEquals(null, $res, $this->last_error);
      }
      $this->assertEquals($expected_code, $res->getStatusCode());
      // retrieve data
      $data = xmlrpc_decode($res->getBody());
      if (is_array($data)) {
         $data['headers'] = $res->getHeaders();
      }
      return $data;
   }
}
