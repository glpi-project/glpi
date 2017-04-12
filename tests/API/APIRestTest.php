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

class APIRestTest extends APIBaseClass {
   protected $http_client;
   protected $base_uri = "";
   protected $last_error = "";

   public function __construct() {
      global $CFG_GLPI;

      $this->http_client = new GuzzleHttp\Client();
      $this->base_uri    = trim($CFG_GLPI['url_base_api'], "/")."/";

      //to make phpunit6 happy
      parent::__construct(null, [], '');
   }

   protected function doHttpRequest($verb = "get", $relative_uri = "", $params = []) {
      if (!empty($relative_uri)) {
         $params['headers']['Content-Type'] = "application/json";
      }
      if (isset($params['multipart'])) {
         // Guzzle lib will automatically push the correct Content-type
         unset($params['headers']['Content-Type']);
      }
      $verb = strtolower($verb);
      if (in_array($verb, ['get', 'post', 'delete', 'put', 'options', 'patch'])) {
         try {
            return $this->http_client->{$verb}($this->base_uri.$relative_uri,
                                                 $params);
         } catch (Exception $e) {
            if ($e instanceof ClientException
                && $e->hasResponse()) {
               $this->last_error = $e->getResponse();
            }
            throw $e;
         }
      }
   }

   protected function query($resource = "", $params = [], $expected_code = 200) {
      $verb         = isset($params['verb'])
                        ? $params['verb']
                        : 'GET';
      $relative_uri = (!in_array($resource, ['getItem', 'getItems', 'createItems',
                                             'updateItems', 'deleteItems'])
                         ? $resource.'/'
                         : '').
                      (isset($params['parent_itemtype'])
                         ? $params['parent_itemtype'].'/'
                         : '').
                      (isset($params['parent_id'])
                         ? $params['parent_id'].'/'
                         : '').
                      (isset($params['itemtype'])
                         ? $params['itemtype'].'/'
                         : '').
                      (isset($params['id'])
                         ? $params['id']
                         : '');
      unset($params['itemtype'],
            $params['id'],
            $params['parent_itemtype'],
            $params['parent_id'],
            $params['verb']);
      // launch query
      try {
         $res = $this->doHttpRequest($verb, $relative_uri, $params);
      } catch (ClientException $e) {
         $response = $e->getResponse();
         $this->assertEquals($expected_code, $response->getStatusCode());
         return;
      }
      //retrieve data
      $body            = $res->getBody();
      $data            = json_decode($body, true);
      if (is_array($data)) {
         $data['headers'] = $res->getHeaders();
      }
      // common tests
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals($expected_code, $res->getStatusCode());
      return $data;
   }

   /**
    * @group  api
    * @covers API::cors
   **/
   public function testCORS() {
      $res = $this->doHttpRequest('OPTIONS', '',
                                         ['headers' => [
                                             'Origin' => "http://localhost",
                                             'Access-Control-Request-Method'  => 'GET',
                                             'Access-Control-Request-Headers' => 'X-Requested-With'
                                         ]]);

      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());
      $headers = $res->getHeaders();
      $this->assertArrayHasKey('Access-Control-Allow-Methods', $headers);
      $this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);
      $this->assertContains('GET', $headers['Access-Control-Allow-Methods'][0]);
      $this->assertContains('PUT', $headers['Access-Control-Allow-Methods'][0]);
      $this->assertContains('POST', $headers['Access-Control-Allow-Methods'][0]);
      $this->assertContains('DELETE', $headers['Access-Control-Allow-Methods'][0]);
      $this->assertContains('OPTIONS', $headers['Access-Control-Allow-Methods'][0]);
      $this->assertContains('origin', $headers['Access-Control-Allow-Headers'][0]);
      $this->assertContains('content-type', $headers['Access-Control-Allow-Headers'][0]);
      $this->assertContains('accept', $headers['Access-Control-Allow-Headers'][0]);
      $this->assertContains('session-token', $headers['Access-Control-Allow-Headers'][0]);
      $this->assertContains('authorization', $headers['Access-Control-Allow-Headers'][0]);
   }

   /**
    * @group  api
    * @covers API::inlineDocumentation
   **/
   public function testInlineDocumentation() {
      $res = $this->doHttpRequest('GET');
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());
      $headers = $res->getHeaders();
      $this->assertArrayHasKey('Content-Type', $headers);
      $this->assertContains('text/html; charset=UTF-8', $headers['Content-Type'][0]);
   }

   /**
    * @group  api
    * @covers API::initSession
   **/
   public function testInitSessionCredentials() {
      $res = $this->doHttpRequest('GET', 'initSession/', ['auth' => [TU_USER, TU_PASS]]);

      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());
      $this->assertContains( "application/json; charset=UTF-8", $res->getHeader('content-type') );

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('session_token', $data);
      return $data['session_token'];
   }

   /**
    * @group  api
    * @covers API::initSession
   **/
   public function testInitSessionUserToken() {
      // retrieve personnal token of TU_USER user
      $user = new User;
      $uid = getItemByTypeName('User', TU_USER, true);
      $user->getFromDB($uid);
      $token = isset($user->fields['personal_token'])?$user->fields['personal_token']:"";
      if (empty($token)) {
         $token = User::getPersonalToken($uid);
      }

      $res = $this->doHttpRequest('GET', 'initSession/',
                                         ['headers' => [
                                             'Authorization' => "user_token $token"
                                         ]]);

      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('session_token', $data);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    */
   public function testBadEndpoint($session_token, $expected_code = 400) {
      parent::testBadEndpoint($session_token, $expected_code);

      $data = $this->query('getItems',
                           ['itemtype'        => 'badEndpoint',
                            'parent_id'       => 0,
                            'parent_itemtype' => 'Entity',
                            'headers'         => [
                            'Session-Token' => $session_token]],
                           $expected_code);
   }

   /**
    * Redefine this test to permits launch of testUpdateItemWithIdInQueryString
    *
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::CreateItems
    */
   public function testCreateItem($session_token) {
      return parent::testCreateItem($session_token);
   }

   /**
     * @group   api
     * @depends testInitSessionCredentials
     * @depends testCreateItem
     */
   public function testUpdateItemWithIdInQueryString($session_token, $computers_id) {
      $data = $this->query('updateItems',
                           ['itemtype' => 'Computer',
                            'id'       => $computers_id,
                            'verb'     => 'PUT',
                            'headers'  => [
                               'Session-Token' => $session_token],
                            'json'     => [
                               'input' => [
                                  'serial' => "abcdefg"]]]);
      $this->assertNotEquals(false, $data);
      unset($data['headers']);
      $computer = array_shift($data);
      $this->assertArrayHasKey($computers_id, $computer);
      $this->assertArrayHasKey('message', $computer);
      $this->assertEquals(true, (bool) $computer[$computers_id]);
      $computer = new Computer;
      $computers_exist = $computer->getFromDB($computers_id);
      $this->assertEquals(true, (bool) $computers_exist);
      $this->assertEquals("abcdefg", $computer->fields['serial']);
   }


   /**
    * @group   api
    * @depends testInitSessionCredentials
    */
   public function testUploadDocument($session_token) {
      // we will try to upload the README.md file
      $document_name = "My document uploaded by api";
      $filename      = "README.md";
      $filecontent   = file_get_contents($filename);

      $data = $this->query('createItems',
                           ['verb'      => 'POST',
                            'itemtype'  => 'Document',
                            'headers'   => [
                              'Session-Token' => $session_token
                            ],
                            'multipart' => [
                              // the document part
                              [
                                 'name'     => 'uploadManifest',
                                 'contents' => json_encode([
                                    'input' => [
                                       'name'       => $document_name,
                                       '_filename'  => [$filename],
                                    ]
                                 ])
                              ],
                              // the FILE part
                              [
                                 'name'     => 'filename[]',
                                 'contents' => $filecontent,
                                 'filename' => $filename
                              ]
                            ]],
                           201);

      $this->assertArrayHasKey('id', $data);
      $documents_id = $data['id'];
      $this->assertEquals(true, is_numeric($documents_id));
      $this->assertEquals(true, $documents_id > 0);
      $this->assertArrayHasKey('message', $data);

      $document        = new Document;
      $documents_exist = (bool) $document->getFromDB($documents_id);
      $this->assertEquals(true, $documents_exist);
      $this->assertEquals('text/plain', $document->fields['mime']);
      $this->assertEquals($document_name, $document->fields['name']);
      $this->assertEquals($filename, $document->fields['filename']);
      $this->assertEquals(true, (strpos($document->fields['filepath'], 'MD/') !== false));
   }
}
