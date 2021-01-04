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

namespace tests\units\Glpi\Api;

use \APIBaseClass;
use Glpi\Tests\Web\Deprecated\Computer_SoftwareLicense;
use Glpi\Tests\Web\Deprecated\Computer_SoftwareVersion;
use Glpi\Tests\Web\Deprecated\TicketFollowup;
use \GuzzleHttp;
use GuzzleHttp\Exception\ClientException;
use ITILFollowup;

/* Test for inc/api/api.class.php */

/**
 * @engine isolate
 */
class APIRest extends APIBaseClass {

   public function beforeTestMethod($method) {
      global $CFG_GLPI;

      // Clear test server log
      if (!file_exists(__DIR__ . '/error.log')) {
         touch(__DIR__ . '/error.log');
      }
      file_put_contents(__DIR__ . '/error.log', "");

      $this->http_client = new GuzzleHttp\Client();
      $this->base_uri    = trim($CFG_GLPI['url_base_api'], "/")."/";

      parent::beforeTestMethod($method);
   }

   public function afterTestMethod($method) {
      global $CFG_GLPI;

      // Check that no errors occured on the test server
      $this->string(file_get_contents(__DIR__ . '/error.log'))->isEmpty();
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

   protected function query(
      $resource = "",
      $params = [],
      $expected_codes = [200],
      $expected_symbol = '',
      bool $no_decode = false
   ) {
      if (!is_array($expected_codes)) {
         $expected_codes = [$expected_codes];
      }

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
         if (!in_array($response->getStatusCode(), $expected_codes)) {
            //throw exceptions not expected
            throw $e;
         }
         $this->array($expected_codes)->contains($response->getStatusCode());
         $body = json_decode($e->getResponse()->getBody());
         $this->array($body)
            ->hasKey('0')
            ->string[0]->isIdenticalTo($expected_symbol);
         return $body;
      }

      // retrieve data
      $body = $res->getBody();

      if ($no_decode) {
         $data = $body;
      } else {
         $data = json_decode($body, true);
         if (is_array($data)) {
            $data['headers'] = $res->getHeaders();
         }
      }

      // common tests
      $this->variable($res)->isNotNull();
      $this->array($expected_codes)->contains($res->getStatusCode());
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

      $res = $this->doHttpRequest('GET', 'initSession?get_full_session=true',
                                         ['headers' => [
                                             'Authorization' => "user_token $token"
                                         ]]);

      $this->variable($res)->isNotNull();
      $this->variable($res->getStatusCode())->isEqualTo(200);

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->variable($data)->isNotFalse();
      $this->array($data)->hasKey('session_token');
      $this->array($data)->hasKey('session');
      $this->integer((int) $data['session']['glpiID'])->isEqualTo($uid);
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
      $ticketTemplate = new \TicketTemplate();
      $ticketTMF = new \TicketTemplateMandatoryField();

      $tt_id = $ticketTemplate->add([
         'entities_id' => getItemByTypeName('Entity', '_test_child_1', true),
         'name'        => 'test'
      ]);
      $this->boolean((bool)$tt_id)->isTrue();

      $ttmf_id = $ticketTMF->add([
         'tickettemplates_id' => $tt_id,
         'num'                => 7
      ]);
      $this->boolean((bool)$ttmf_id)->isTrue();

      $data = $this->query('getItems',
                           ['query'     => [
                               'searchText' => ['tickettemplates_id' => "^".$tt_id."$"]],
                            'itemtype'   => 'TicketTemplateMandatoryField',
                            'headers'    => ['Session-Token' => $this->session_token]],
                           200);
      if (isset($data['headers'])) {
         unset($data['headers']);
      }
      $this->integer(count($data))->isEqualTo(1);
   }

   /**
    * @tags   api
    * @covers API::userPicture
    */
   public function testUserPicture() {
      $pic = "test_picture.png";
      $params = ['headers' => ['Session-Token' => $this->session_token]];
      $id = getItemByTypeName('User', 'glpi', true);
      $user = new \User();

      /**
       * Case 1: normal execution
       */

      // Copy pic to tmp folder so it can be set to a user
      copy("tests/$pic", GLPI_TMP_DIR . "/$pic");

      // Load GLPI user
      $this->boolean($user->getFromDB($id))->isTrue();

      // Set a pic URL
      $success = $user->update([
         'id'      => $id,
         '_picture' => [$pic],
      ]);
      $this->boolean($success)->isTrue();

      // Get updated pic url
      $pic = $user->fields['picture'];
      $this->string($pic)->isNotEmpty();

      // Check pic was moved correctly into _picture folder
      $this->boolean(file_exists(GLPI_PICTURE_DIR . "/$pic"))->isTrue();
      $file_content = file_get_contents(GLPI_PICTURE_DIR . "/$pic");
      $this->string($file_content)->isNotEmpty();

      // Request
      $response = $this->query("User/$id/Picture", $params, 200, '', true);
      $this->string($response->__toString())->isEqualTo($file_content);

      /**
       * Case 2: user doens't exist
       */

      // Request
      $response = $this->query("User/99999999/Picture", $params, 400, "ERROR");
      $this->array($response)->hasSize(2);
      $this->string($response[1])->contains("Bad request: user with id '99999999' not found");

      /**
       * Case 3: user with no pictures
       */

      // Remove pic URL
      $success = $user->update([
         'id'             => $id,
         '_blank_picture' => true,
      ]);
      $this->boolean($success)->isTrue();

      // Request
      $response = $this->query("User/$id/Picture", $params, 204);
      $this->variable($response)->isNull();
   }

   protected function deprecatedProvider() {
      return [
         ['provider' => TicketFollowup::class],
         ['provider' => Computer_SoftwareVersion::class],
         ['provider' => Computer_SoftwareLicense::class],
      ];
   }

   /**
    * @dataProvider deprecatedProvider
    */
   public function testDeprecatedGetItem(string $provider) {
      // Get params from provider
      $deprecated_itemtype = $provider::getDeprecatedType();
      $itemtype            = $provider::getCurrentType();
      $deprecated_fields   = $provider::getDeprecatedFields();
      $add_input           = $provider::getCurrentAddInput();

      $headers = ['Session-Token' => $this->session_token];

      // Insert data for tests
      $item = new $itemtype();
      $item_id = $item->add($add_input);
      $this->integer($item_id);

      // Call API
      $data = $this->query("$deprecated_itemtype/$item_id", [
         'headers' => $headers,
      ], 200);
      $this->array($data)
         ->hasSize(count($deprecated_fields) + 1) // + 1 for headers
         ->hasKeys($deprecated_fields);

      // Clean db to prevent unicity failure on next run
      $item->delete(['id' => $item_id]);
   }

   /**
    * @dataProvider deprecatedProvider
    */
   public function testDeprecatedGetItems(string $provider) {
      // Get params from provider
      $deprecated_itemtype = $provider::getDeprecatedType();
      $itemtype            = $provider::getCurrentType();
      $deprecated_fields   = $provider::getDeprecatedFields();
      $add_input           = $provider::getCurrentAddInput();

      $headers = ['Session-Token' => $this->session_token];

      // Insert data for tests (we need at least one item)
      $item = new $itemtype();
      $item_id = $item->add($add_input);
      $this->integer($item_id);

      // Call API
      $data = $this->query("$deprecated_itemtype", [
         'headers' => $headers,
      ], [200, 206]);
      $this->array($data);
      unset($data["headers"]);

      foreach ($data as $row) {
         $this->array($row)
            ->hasSize(count($deprecated_fields))
            ->hasKeys($deprecated_fields);
      }

      // Clean db to prevent unicity failure on next run
      $item->delete(['id' => $item_id]);
   }

   /**
    * @dataProvider deprecatedProvider
    */
   public function testDeprecatedCreateItems(string $provider) {
      // Get params from provider
      $deprecated_itemtype   = $provider::getDeprecatedType();
      $itemtype              = $provider::getCurrentType();
      $input                 = $provider::getDeprecatedAddInput();
      $expected_after_insert = $provider::getExpectedAfterInsert();

      $headers = ['Session-Token' => $this->session_token];

      $item = new $itemtype();

      // Call API
      $data = $this->query("$deprecated_itemtype", [
         'headers' => $headers,
         'verb'    => "POST",
         'json'    => ['input' => $input]
      ], 201);

      $this->integer($data['id']);
      $this->boolean($item->getFromDB($data['id']))->isTrue();

      foreach ($expected_after_insert as $field => $value) {
         $this->variable($item->fields[$field])->isEqualTo($value);
      }

      // Clean db to prevent unicity failure on next run
      $item->delete(['id' => $data['id']]);
   }

   /**
    * @dataProvider deprecatedProvider
    */
   public function testDeprecatedUpdateItems(string $provider) {
      // Get params from provider
      $deprecated_itemtype   = $provider::getDeprecatedType();
      $itemtype              = $provider::getCurrentType();
      $add_input             = $provider::getCurrentAddInput();
      $update_input          = $provider::getDeprecatedUpdateInput();
      $expected_after_update = $provider::getExpectedAfterUpdate();

      $headers = ['Session-Token' => $this->session_token];

      // Insert data for tests
      $item = new $itemtype();
      $item_id = $item->add($add_input);
      $this->integer($item_id);

      // Call API
      $this->query("$deprecated_itemtype/$item_id", [
         'headers' => $headers,
         'verb'    => "PUT",
         'json'    => ['input' => $update_input]
      ], 200);

      // Check expected values
      $this->boolean($item->getFromDB($item_id))->isTrue();

      foreach ($expected_after_update as $field => $value) {
         $this->variable($item->fields[$field])->isEqualTo($value);
      }

      // Clean db to prevent unicity failure on next run
      $item->delete(['id' => $item_id]);
   }

   /**
    * @dataProvider deprecatedProvider
    */
   public function testDeprecatedDeleteItems(string $provider) {
      // Get params from provider
      $deprecated_itemtype   = $provider::getDeprecatedType();
      $itemtype              = $provider::getCurrentType();
      $add_input             = $provider::getCurrentAddInput();

      $headers = ['Session-Token' => $this->session_token];

      // Insert data for tests
      $item = new $itemtype();
      $item_id = $item->add($add_input);
      $this->integer($item_id);

      // Call API
      $this->query("$deprecated_itemtype/$item_id", [
         'headers' => $headers,
         'verb'    => "DELETE",
      ], 200, "", true);

      $this->boolean($item->getFromDB($item_id))->isFalse();
   }

   /**
    * @dataProvider deprecatedProvider
    */
   public function testDeprecatedListSearchOptions(string $provider) {
      // Get params from provider
      $deprecated_itemtype   = $provider::getDeprecatedType();

      $headers = ['Session-Token' => $this->session_token];

      $data = $this->query("listSearchOptions/$deprecated_itemtype/", [
         'headers' => $headers,
      ]);

      $expected = file_get_contents(
         __DIR__ . "/../deprecated-searchoptions/$deprecated_itemtype.json"
      );
      $this->string($expected)->isNotEmpty();

      unset($data['headers']);
      $json_data = json_encode($data, JSON_PRETTY_PRINT);
      $this->string($json_data)->isEqualTo($expected);
   }

   /**
    * @dataProvider deprecatedProvider
    */
   public function testDeprecatedSearch(string $provider) {
      // Get params from provider
      $deprecated_itemtype       = $provider::getDeprecatedType();
      $deprecated_itemtype_query = $provider::getDeprecatedSearchQuery();
      $itemtype                  = $provider::getCurrentType();
      $itemtype_query            = $provider::getCurrentSearchQuery();

      $headers = ['Session-Token' => $this->session_token];

      $deprecated_data = $this->query(
         "search/$deprecated_itemtype?$deprecated_itemtype_query",
         ['headers' => $headers],
         [200, 206]
      );

      $data = $this->query(
         "search/$itemtype?$itemtype_query",
         ['headers' => $headers],
         [200, 206]
      );
      $this->string($deprecated_data['rawdata']['sql']['search'])
         ->isEqualTo($data['rawdata']['sql']['search']);
   }
}
