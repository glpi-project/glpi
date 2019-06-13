<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

use \APIBaseClass;
use \GuzzleHttp;
use GuzzleHttp\Exception\ClientException;

/* Test for inc/api.class.php */

/**
 * @engine isolate
 */
class APIRest extends APIBaseClass {

   public function beforeTestMethod($method) {
      global $CFG_GLPI;

      $this->http_client = new GuzzleHttp\Client();
      $this->base_uri    = trim($CFG_GLPI['url_base_api'], "/")."/";

      parent::beforeTestMethod($method);
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
         } catch (\Exception $e) {
            throw $e;
         }
      }
   }

   protected function query($resource = "", $params = [], $expected_code = 200, $expected_symbol = '') {
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
         if ($response->getStatusCode() != $expected_code) {
            //throw exceptions not expected
            throw $e;
         }
         $this->variable($response->getStatusCode())->isEqualTo($expected_code);
         $body = json_decode($e->getResponse()->getBody());
         $this->array($body)
            ->hasKey('0')
            ->string[0]->isIdenticalTo($expected_symbol);
         return;
      }
      //retrieve data
      $body            = $res->getBody();
      $data            = json_decode($body, true);
      if (is_array($data)) {
         $data['headers'] = $res->getHeaders();
      }
      // common tests
      $this->variable($res)->isNotNull();
      $this->variable($res->getStatusCode())->isEqualTo($expected_code);
      return $data;
   }

   /**
    * @tags   api
    * @covers API::cors
   **/
   public function testCORS() {
      $res = $this->doHttpRequest('OPTIONS', '',
                                         ['headers' => [
                                             'Origin' => "http://localhost",
                                             'Access-Control-Request-Method'  => 'GET',
                                             'Access-Control-Request-Headers' => 'X-Requested-With'
                                         ]]);

      $this->variable($res)->isNotNull();
      $this->variable($res->getStatusCode())->isEqualTo(200);
      $headers = $res->getHeaders();
      $this->array($headers)
         ->hasKey('Access-Control-Allow-Methods')
         ->hasKey('Access-Control-Allow-Headers');

      $this->string($headers['Access-Control-Allow-Methods'][0])
         ->contains('GET')
         ->contains('PUT')
         ->contains('POST')
         ->contains('DELETE')
         ->contains('OPTIONS');

      $this->string($headers['Access-Control-Allow-Headers'][0])
         ->contains('origin')
         ->contains('content-type')
         ->contains('accept')
         ->contains('session-token')
         ->contains('authorization');
   }

   /**
    * @tags   api
    * @covers API::inlineDocumentation
   **/
   public function testInlineDocumentation() {
      $res = $this->doHttpRequest('GET');
      $this->variable($res)->isNotNull();
      $this->variable($res->getStatusCode())->isEqualTo(200);
      $headers = $res->getHeaders();
      $this->array($headers)->hasKey('Content-Type');
      $this->string($headers['Content-Type'][0])->isIdenticalTo('text/html; charset=UTF-8');
   }

   /**
    * @tags   api
    * @covers API::initSession
   **/
   public function initSessionCredentials() {
      $res = $this->doHttpRequest('GET', 'initSession/', ['auth' => [TU_USER, TU_PASS]]);

      $this->variable($res)->isNotNull();
      $this->variable($res->getStatusCode())->isEqualTo(200);
      $this->array($res->getHeader('content-type'))->contains('application/json; charset=UTF-8');

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->variable($data)->isNotFalse();
      $this->array($data)->hasKey('session_token');
      $this->session_token = $data['session_token'];
   }

   /**
    * @tags   api
    * @covers API::initSession
   **/
   public function testInitSessionUserToken() {
      // retrieve personnal token of TU_USER user
      $user = new \User;
      $uid = getItemByTypeName('User', TU_USER, true);
      $this->boolean((bool)$user->getFromDB($uid))->isTrue();
      $token = isset($user->fields['api_token'])?$user->fields['api_token']:"";
      if (empty($token)) {
         $token = $user->getAuthToken('api_token');
      }

      $res = $this->doHttpRequest('GET', 'initSession/',
                                         ['headers' => [
                                             'Authorization' => "user_token $token"
                                         ]]);

      $this->variable($res)->isNotNull();
      $this->variable($res->getStatusCode())->isEqualTo(200);

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->variable($data)->isNotFalse();
      $this->array($data)->hasKey('session_token');
   }

   /**
    * @tags    api
    */
   public function testBadEndpoint() {
      parent::badEndpoint(400, 'ERROR_RESOURCE_NOT_FOUND_NOR_COMMONDBTM');

      $data = $this->query('getItems',
                           ['itemtype'        => 'badEndpoint',
                            'parent_id'       => 0,
                            'parent_itemtype' => 'Entity',
                            'headers'         => [
                            'Session-Token' => $this->session_token]],
                           400,
                           'ERROR_RESOURCE_NOT_FOUND_NOR_COMMONDBTM');
   }

   /**
     * @tags    api
     */
   public function testUpdateItemWithIdInQueryString() {
      $computer = $this->createComputer();
      $computers_id = $computer->getID();

      $data = $this->query('updateItems',
                           ['itemtype' => 'Computer',
                            'id'       => $computers_id,
                            'verb'     => 'PUT',
                            'headers'  => [
                               'Session-Token' => $this->session_token],
                            'json'     => [
                               'input' => [
                                  'serial' => "abcdefg"]]]);

      $this->variable($data)->isNotFalse();

      $this->array($data)->hasKey('headers');
      unset($data['headers']);

      $computer = array_shift($data);
      $this->array($computer)
         ->hasKey($computers_id)
         ->hasKey('message');
      $this->boolean((bool)$computer[$computers_id])->isTrue();

      $computer = new \Computer;
      $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();
      $this->string($computer->fields['serial'])->isIdenticalTo('abcdefg');
   }


   /**
    * @tags    api
    */
   public function testUploadDocument() {
      // we will try to upload the README.md file
      $document_name = "My document uploaded by api";
      $filename      = "README.md";
      $filecontent   = file_get_contents($filename);

      $data = $this->query('createItems',
                           ['verb'      => 'POST',
                            'itemtype'  => 'Document',
                            'headers'   => [
                              'Session-Token' => $this->session_token
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

      $this->array($data)
         ->hasKey('id')
         ->hasKey('message');
      $documents_id = $data['id'];
      $this->boolean(is_numeric($documents_id))->isTrue();
      $this->integer((int)$documents_id)->isGreaterThan(0);

      $document        = new \Document;
      $this->boolean((bool)$document->getFromDB($documents_id));

      $this->array($document->fields)
         ->string['mime']->isIdenticalTo('text/plain')
         ->string['name']->isIdenticalTo($document_name)
         ->string['filename']->isIdenticalTo($filename);

      $this->string($document->fields['filepath'])->contains('MD/');
   }

   /**
    * @tags    api
    * @covers  API::updateItems
    */
   public function testUpdateItem() {
      //parent::testUpdateItem($session_token, $computers_id);

      //try to update an item without input
      $data = $this->query('updateItems',
            ['itemtype' => 'Computer',
                  'verb'     => 'PUT',
                  'headers'  => ['Session-Token' => $this->session_token],
                  'json'     => []],
            400,
            'ERROR_JSON_PAYLOAD_INVALID');
   }

   /**
    * @tags    api
    * @covers  API::getItems
    */
   public function testGetItemsCommonDBChild() {
      // test the case have DBChild not have entities_id
      $ticketTemplate = new \ITILTemplate();
      $ticketTMF = new \ITILTemplateMandatoryField();

      $tt_id = $ticketTemplate->add([
         'entities_id' => 0,
         'name'        => 'test'
      ]);
      $this->boolean((bool)$tt_id)->isTrue();

      $ttmf_id = $ticketTMF->add([
         'itiltemplates_id' => $tt_id,
         'num'                => 7
      ]);
      $this->boolean((bool)$ttmf_id)->isTrue();

      $data = $this->query('getItems',
                           ['query'     => [
                               'searchText' => ['itiltemplates_id' => "^".$tt_id."$"]],
                            'itemtype'   => 'ITILTemplateMandatoryField',
                            'headers'    => ['Session-Token' => $this->session_token]],
                           200);
      if (isset($data['headers'])) {
         unset($data['headers']);
      }
      $this->integer(count($data))->isEqualTo(1);
   }
}
