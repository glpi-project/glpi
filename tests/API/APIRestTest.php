<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2015 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/* Test for inc/api.class.php */

use GuzzleHttp\Exception\ClientException;

class APIRestTest extends PHPUnit_Framework_TestCase {
   protected $http_client;
   protected $base_uri = "";
   protected $last_error = "";


   protected function setUp() {
      global $CFG_GLPI;

      $this->http_client = new GuzzleHttp\Client();
      $this->base_uri    = trim($CFG_GLPI['url_base_api'], "/")."/";

      // enable api config
      $config = new Config;
      $config->update(array('id'                              => 1,
                            'enable_api'                      => true,
                            'enable_api_login_credentials'    => true,
                            'enable_api_login_external_token' => true));
   }


   protected function doHttpRequest($method = "get", $relative_uri = "", $params = array()) {
      if (!empty($relative_uri)) {
         $params['headers']['Content-Type'] = "application/json";
      }
      $method = strtolower($method);
      if (in_array($method, array('get', 'post', 'delete', 'put', 'options', 'patch'))) {
         try {
            return $this->http_client->{$method}($this->base_uri.$relative_uri,
                                                 $params);
         } catch (Exception $e) {
            if ($e->hasResponse()) {
               $this->last_error = $e->getResponse();
            }
         }
      }
   }

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
      $this->assertContains('GET',           $headers['Access-Control-Allow-Methods'][0]);
      $this->assertContains('PUT',           $headers['Access-Control-Allow-Methods'][0]);
      $this->assertContains('POST',          $headers['Access-Control-Allow-Methods'][0]);
      $this->assertContains('DELETE',        $headers['Access-Control-Allow-Methods'][0]);
      $this->assertContains('OPTIONS',       $headers['Access-Control-Allow-Methods'][0]);
      $this->assertContains('origin',        $headers['Access-Control-Allow-Headers'][0]);
      $this->assertContains('content-type',  $headers['Access-Control-Allow-Headers'][0]);
      $this->assertContains('accept',        $headers['Access-Control-Allow-Headers'][0]);
      $this->assertContains('session-token', $headers['Access-Control-Allow-Headers'][0]);
      $this->assertContains('authorization', $headers['Access-Control-Allow-Headers'][0]);
   }

   public function testInlineDocumentation() {
      $res = $this->doHttpRequest('GET');
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());
      $headers = $res->getHeaders();
      $this->assertArrayHasKey('Content-Type', $headers);
      $this->assertContains('text/html; charset=UTF-8', $headers['Content-Type'][0]);
   }


   public function testInitSessionCredentials() {
      $res = $this->doHttpRequest('GET', 'initSession/', ['auth' => ['glpi', 'glpi']]);


      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());
      $this->assertContains( "application/json; charset=UTF-8", $res->getHeader('content-type') );

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('session_token', $data);
      return $data['session_token'];
   }


   public function testInitSessionUserToken() {
      // retrieve personnal token of 'glpi' user
      $user = new User;
      $user->getFromDB(2);
      $token = isset($user->fields['personnal_token'])?$user->fields['personnal_token']:"";
      if (empty($token)) {
         $token = User::getPersonalToken(2);
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
     * @depends testInitSessionCredentials
     */
   public function testChangeActiveEntities($session_token) {
      $res = $this->doHttpRequest('POST', 'changeActiveEntities/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'json' => [
                                             'entities_id'   => 'all',
                                             'is_recursive'  => true]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetMyEntities($session_token) {
      $res = $this->doHttpRequest('GET', 'getMyEntities/',
                                         ['headers' => [
                                             'Session-Token' => $session_token]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('myentities', $data); // check presence of first entity
      $this->assertArrayHasKey('id', $data['myentities'][0]); // check presence of first entity
      $this->assertEquals(0, $data['myentities'][0]['id']); // check presence of root entity
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetActiveEntities($session_token) {
      $res = $this->doHttpRequest('GET', 'getActiveEntities/',
                                         ['headers' => [
                                             'Session-Token' => $session_token]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('active_entity', $data);
      $this->assertArrayHasKey('id', $data['active_entity']);
      $this->assertArrayHasKey('active_entity_recursive', $data['active_entity']);
      $this->assertArrayHasKey('active_entities', $data['active_entity']);
      $this->assertTrue(is_array($data['active_entity']['active_entities']));
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testChangeActiveProfile($session_token) {
      $res = $this->doHttpRequest('POST', 'changeActiveProfile/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'json' => [
                                             'profiles_id'   => 4]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetMyProfiles($session_token) {
      $res = $this->doHttpRequest('GET', 'getMyProfiles/',
                                         ['headers' => [
                                             'Session-Token' => $session_token]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('myprofiles', $data);  // check presence of root key
      $this->assertArrayHasKey('id', $data['myprofiles'][0]);  // check presence of id key in first entity
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetActiveProfile($session_token) {
      $res = $this->doHttpRequest('GET', 'getActiveProfile/',
                                         ['headers' => [
                                             'Session-Token' => $session_token]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('active_profile', $data);
      $this->assertArrayHasKey('id', $data['active_profile']);
      $this->assertArrayHasKey('name', $data['active_profile']);
      $this->assertArrayHasKey('interface', $data['active_profile']);
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetFullSession($session_token) {
      $res = $this->doHttpRequest('GET', 'getFullSession/',
                                         ['headers' => [
                                             'Session-Token' => $session_token]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('session', $data);
      $this->assertArrayHasKey('glpiID', $data['session']);
      $this->assertArrayHasKey('glpiname', $data['session']);
      $this->assertArrayHasKey('glpiroot', $data['session']);
      $this->assertArrayHasKey('glpilanguage', $data['session']);
      $this->assertArrayHasKey('glpilist_limit', $data['session']);
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetMultipleItems($session_token) {
      // Get the User 'glpi' and the root entity in the same query
      $res = $this->doHttpRequest('GET', 'getMultipleItems',
                                         ['headers' => ['Session-Token' => $session_token],
                                          'query' =>   [
                                             'items'            => [['itemtype' => 'User',
                                                                     'items_id' => 2],
                                                                    ['itemtype' => 'Entity',
                                                                     'items_id' => 0]],
                                             'with_logs'        => true,
                                             'expand_dropdowns' => true]]);
      $this->assertEquals(200, $res->getStatusCode());
      $body = $res->getBody();
      $data = json_decode($body, true);

      $this->assertEquals(true, is_array($data));
      $this->assertEquals(2, count($data));

      foreach($data as $item) {
         $this->assertArrayHasKey('id', $item);
         $this->assertArrayHasKey('name', $item);
         $this->assertArrayNotHasKey('password', $item);
         $this->assertArrayHasKey('entities_id', $item);
         $this->assertArrayHasKey('links', $item);
         $this->assertFalse(is_numeric($item['entities_id'])); // for expand_dropdowns
         $this->assertArrayHasKey('_logs', $item); // with_logs == true
      }
   }

   /**
     * @depends testInitSessionCredentials
     */
   public function testListSearchOptions($session_token) {
      // test retrieve all users
      $res = $this->doHttpRequest('GET', 'listSearchOptions/Computer/',
                                         ['headers' => [
                                             'Session-Token' => $session_token]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertGreaterThanOrEqual(128, count($data));
      $this->assertEquals('Name', $data[1]['name']);
      $this->assertEquals('glpi_computers', $data[1]['table']);
      $this->assertEquals('name', $data[1]['field']);
      $this->assertEquals('itemlink', $data[1]['datatype']);
      $this->assertEquals(array('contains', 'equals', 'notequals'),
                                 $data[1]['available_searchtypes']);
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testListSearch($session_token) {
      // test retrieve all users
      $res = $this->doHttpRequest('GET', 'search/User/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'query' => [
                                             'sort'          => 19,
                                             'order'         => 'DESC',
                                             'range'         => '0-10',
                                             'forcedisplay'  => '81',
                                             'rawdata'       => true]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $headers = $res->getHeaders();
      $this->assertArrayHasKey('Accept-Range', $headers);
      $this->assertContains('User', $headers['Accept-Range'][0]);
      $this->assertArrayHasKey('Content-Range', $headers);

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('totalcount', $data);
      $this->assertArrayHasKey('count', $data);
      $this->assertArrayHasKey('sort', $data);
      $this->assertArrayHasKey('order', $data);
      $this->assertArrayHasKey('rawdata', $data);
      $this->assertEquals(8, count($data['rawdata']));

      $first_user = array_shift($data['data']);
      $second_user = array_shift($data['data']);
      $this->assertArrayHasKey(81, $first_user);
      $this->assertArrayHasKey(81, $second_user);
      $first_user_date_mod = strtotime($first_user[19]);
      $second_user_date_mod = strtotime($second_user[19]);
      $this->assertLessThanOrEqual($first_user_date_mod, $second_user_date_mod);
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testListSearchPartial($session_token) {
      // test retrieve partial users
      $res = $this->doHttpRequest('GET', 'search/User/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'query' => [
                                             'sort'          => 19,
                                             'order'         => 'DESC',
                                             'range'         => '0-2',
                                             'forcedisplay'  => '81',
                                             'rawdata'       => true]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(206, $res->getStatusCode());

      $headers = $res->getHeaders();
      $this->assertArrayHasKey('Accept-Range', $headers);
      $this->assertContains('User', $headers['Accept-Range'][0]);
      $this->assertArrayHasKey('Content-Range', $headers);

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('totalcount', $data);
      $this->assertArrayHasKey('count', $data);
      $this->assertArrayHasKey('sort', $data);
      $this->assertArrayHasKey('order', $data);
      $this->assertArrayHasKey('rawdata', $data);
      $this->assertEquals(8, count($data['rawdata']));

      $first_user = array_shift($data['data']);
      $second_user = array_shift($data['data']);
      $this->assertArrayHasKey(81, $first_user);
      $this->assertArrayHasKey(81, $second_user);
      $first_user_date_mod = strtotime($first_user[19]);
      $second_user_date_mod = strtotime($second_user[19]);
      $this->assertLessThanOrEqual($first_user_date_mod, $second_user_date_mod);
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testBadEndpoint($session_token) {
      try {
         $res = $this->doHttpRequest('GET', 'badEndpoint/',
                                            ['headers' => [
                                             'Session-Token' => $session_token]]);
      } catch (ClientException $e) {
         $response = $e->getResponse();
         $this->assertEquals(400, $response->getStatusCode());
      }

      try {
         $res = $this->doHttpRequest('GET', 'Entity/0/badEndpoint/',
                                            ['headers' => [
                                             'Session-Token' => $session_token]]);
      } catch (ClientException $e) {
         $response = $e->getResponse();
         $this->assertEquals(400, $response->getStatusCode());
      }
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testCreateItem($session_token) {
      $res = $this->doHttpRequest('POST', 'Computer/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'json' => [
                                             'input'         => [
                                                'name' => "My computer 1"]]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(201, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $computers_id = $data['id'];
      $this->assertEquals(true, is_numeric($computers_id));
      $this->assertEquals(true, $computers_id > 0);

      $computer = new Computer;
      $computers_exist = $computer->getFromDB($computers_id);
      $this->assertEquals(true, (bool) $computers_exist);


      // create a network port for the previous computer
      $res = $this->doHttpRequest('POST', 'NetworkPort/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'json' => [
                                             'input'         => [
                                                'instantiation_type'       => "NetworkPortEthernet",
                                                'name'                     => "test port",
                                                'logical_number'           => 1,
                                                'items_id'                 => $computers_id,
                                                'itemtype'                 => "Computer",
                                                'NetworkName_name'         => "testname",
                                                'NetworkName_fqdns_id'     => 0,
                                                'NetworkName__ipaddresses' =>
                                                   array(-1 => "1.2.3.4")]]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(201, $res->getStatusCode());
      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $netports_id = $data['id'];

      $res = $this->doHttpRequest('POST', 'Notepad/',
                                          ['headers' => [
                                                'Session-Token' => $session_token],
                                          'json' => [
                                             'input'            => [ 
                                                'itemtype'                 => 'Computer',
                                                'items_id'                 => $computers_id,
                                                'content'                  => 'note about a computer'
                                          ]]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(201, $res->getStatusCode());
      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);

      return $computers_id;
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testCreateItems($session_token) {
      $res = $this->doHttpRequest('POST', 'Computer/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'json' => [
                                             'input'         => [[
                                                'name' => "My computer 2"
                                             ],[
                                                'name' => "My computer 3"]]]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(201, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $first_computer = $data[0];
      $secnd_computer = $data[1];
      $this->assertArrayHasKey('id', $first_computer);
      $this->assertArrayHasKey('id', $secnd_computer);
      $this->assertEquals(true, is_numeric($first_computer['id']));
      $this->assertEquals(true, is_numeric($secnd_computer['id']));
      $this->assertEquals(true, $first_computer['id'] > 0);
      $this->assertEquals(true, $secnd_computer['id'] > 0);


      $computer = new Computer;
      $computers_exist = $computer->getFromDB($first_computer['id']);
      $this->assertEquals(true, (bool) $computers_exist);
      $computers_exist = $computer->getFromDB($secnd_computer['id']);
      $this->assertEquals(true, (bool) $computers_exist);

      return $data;
   }


   /**
     * @depends testInitSessionCredentials
     * @depends testCreateItem
     */
   public function testGetItem($session_token, $computers_id) {
      // Get the User 'glpi'
      $res = $this->doHttpRequest('GET', 'User/2/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'query' => [
                                             'expand_dropdowns' => true,
                                             'with_logs'        => true]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('entities_id', $data);
      $this->assertArrayHasKey('links', $data);
      $this->assertFalse(is_numeric($data['entities_id'])); // for expand_dropdowns
      $this->assertArrayHasKey('_logs', $data); // with_logs == true

      // Get the root-entity
      $res = $this->doHttpRequest('GET', 'Entity/0',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'query' => [
                                             'get_hateoas'   => false]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('completename', $data);
      $this->assertArrayNotHasKey('links', $data); // get_hateoas == false


      // Get the previously created 'computer 1'
      $res = $this->doHttpRequest('GET', "Computer/$computers_id",
                                         ['headers' => [
                                             'Session-Token'     => $session_token],
                                          'query' => [
                                             'with_networkports' => true]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('_networkports', $data);
      $this->assertArrayHasKey('NetworkName', $data['_networkports']['NetworkPortEthernet'][0]);
      $networkname = $data['_networkports']['NetworkPortEthernet'][0]['NetworkName'];
      $this->assertArrayHasKey('IPAddress', $networkname);
      $this->assertArrayHasKey('FQDN', $networkname);
      $this->assertArrayHasKey('id', $networkname['IPAddress'][0]);
      $this->assertArrayHasKey('name', $networkname['IPAddress'][0]);
      $this->assertArrayHasKey('IPNetwork', $networkname['IPAddress'][0]);
      $this->assertEquals('1.2.3.4', $networkname['IPAddress'][0]['name']);
   }

   /**
     * @depends testInitSessionCredentials
     * @depends testCreateItem
     */
   public function testGetItemWithNotes($session_token, $computers_id) {
      // Get the previously created 'computer 1'
      $res = $this->doHttpRequest('GET', "Computer/$computers_id",
            ['headers' => [
                  'Session-Token'     => $session_token],
                  'query' => [
                        'with_notes' => true]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());
      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('_notes', $data);
      $notes = $data['_notes'];
      $this->assertArrayHasKey('id', $notes[0]);
      $this->assertArrayHasKey('itemtype', $notes[0]);
      $this->assertArrayHasKey('items_id', $notes[0]);
      $this->assertArrayHasKey('id', $notes[0]);
      $this->assertArrayHasKey('users_id', $notes[0]);
      $this->assertArrayHasKey('content', $notes[0]);
   }

   /**
     * @depends testInitSessionCredentials
     */
   public function testGetItems($session_token) {
      // test retrieve all users
      $res = $this->doHttpRequest('GET', 'User/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'query' => [
                                             'expand_dropdowns' => true]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());
      $body = $res->getBody();
      $data = json_decode($body, true);

      $this->assertGreaterThanOrEqual(4, count($data));
      $this->assertArrayHasKey('id', $data[0]);
      $this->assertArrayHasKey('name', $data[0]);
      $this->assertArrayNotHasKey('password', $data[0]);
      $this->assertArrayHasKey('is_active', $data[0]);
      $this->assertFalse(is_numeric($data[0]['entities_id'])); // for expand_dropdowns


      // test retrieve partial users
      $res = $this->doHttpRequest('GET', 'User/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'query' => [
                                             'range' => '0-1',
                                             'expand_dropdowns' => true]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(206, $res->getStatusCode());
      $body = $res->getBody();
      $data = json_decode($body, true);

      $this->assertEquals(2, count($data));
      $this->assertArrayHasKey('id', $data[0]);
      $this->assertArrayHasKey('name', $data[0]);
      $this->assertArrayNotHasKey('password', $data[0]);
      $this->assertArrayHasKey('is_active', $data[0]);
      $this->assertFalse(is_numeric($data[0]['entities_id'])); // for expand_dropdowns


      // test retrieve invalid range of users
      try {
         $res = $this->doHttpRequest('GET', 'User/',
                                            ['headers' => [
                                                'Session-Token' => $session_token],
                                             'query' => [
                                                'range' => '100-105',
                                                'expand_dropdowns' => true]]);
      } catch (ClientException $e) {
         $response = $e->getResponse();
         $this->assertEquals(400, $response->getStatusCode());
      }


      // Test only_id param
      $res = $this->doHttpRequest('GET', 'User/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'query' => [
                                             'only_id'       => true]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertGreaterThanOrEqual(4, count($data));
      $this->assertArrayHasKey('id', $data[0]);
      $this->assertArrayNotHasKey('name', $data[0]);
      $this->assertArrayNotHasKey('password', $data[0]);
      $this->assertArrayNotHasKey('is_active', $data[0]);
   }


   /**
    * This function test https://github.com/glpi-project/glpi/issues/1103
    * A post-only user could retrieve tickets of others users when requesting itemtype
    * without first letter in uppercase
    */
   public function testgetItemsForPostonly() {
      // init session for postonly
      $res = $this->doHttpRequest('GET', 'initSession/', ['auth' => ['post-only', 'postonly']]);
      $body = $res->getBody();
      $data = json_decode($body, true);


      // create a ticket for another user (glpi - super-admin)
      $ticket = new Ticket;
      $tickets_id = $ticket->add(array('name'                => 'test post-only',
                                       'content'             => 'test post-only',
                                       '_users_id_requester' => 2));

      // try to access this ticket with post-only
      try {
         $res = $this->doHttpRequest('GET', "ticket/$tickets_id",
                                            ['headers' => [
                                                'Session-Token' => $data['session_token']]]);
      } catch (ClientException $e) {
         $response = $e->getResponse();
         $this->assertEquals(401, $res->getStatusCode());
      }

      // try to access ticket list (we should get empty return)
      $res = $this->doHttpRequest('GET', 'ticket/',
                                         ['headers' => [
                                             'Session-Token' => $data['session_token']]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());
      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertEquals(0, count($data));

      // delete ticket
      $ticket->delete(array('id' => $tickets_id), true);
   }


   /**
     * @depends testInitSessionCredentials
     * @depends testCreateItem
     */
   public function testUpdateItem($session_token, $computers_id) {
      $res = $this->doHttpRequest('PUT', 'Computer/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'json' => [
                                             'input'         => [
                                                'id'     => $computers_id,
                                                'serial' => "abcdef"]]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $computer = array_shift($data);
      $this->assertArrayHasKey($computers_id, $computer);
      $this->assertEquals(true, (bool) $computer[$computers_id]);

      $computer = new Computer;
      $computers_exist = $computer->getFromDB($computers_id);
      $this->assertEquals(true, (bool) $computers_exist);
      $this->assertEquals("abcdef", $computer->fields['serial']);
   }


   /**
     * @depends testInitSessionCredentials
     * @depends testCreateItem
     */
   public function testUpdateItemWithIdInQueryString($session_token, $computers_id) {
      $res = $this->doHttpRequest('PUT', "Computer/$computers_id",
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'json' => [
                                             'input'         => [
                                                'serial' => "abcdefg"]]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $computer = array_shift($data);
      $this->assertArrayHasKey($computers_id, $computer);
      $this->assertEquals(true, (bool) $computer[$computers_id]);

      $computer = new Computer;
      $computers_exist = $computer->getFromDB($computers_id);
      $this->assertEquals(true, (bool) $computers_exist);
      $this->assertEquals("abcdefg", $computer->fields['serial']);
   }


   /**
     * @depends testInitSessionCredentials
     * @depends testCreateItems
     */
   public function testUpdateItems($session_token, $computers_id_collection) {
      $input    = array();
      $computer = new Computer;
      foreach($computers_id_collection as $key => $computers_id) {
         $input[] = ['id'          => $computers_id['id'],
                     'otherserial' => "abcdef"];
      }
      $res = $this->doHttpRequest('PUT', 'Computer/',
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'json' => [
                                             'input'         => $input]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      foreach($data as $index => $row) {
         $computers_id = $computers_id_collection[$index]['id'];
         $this->assertArrayHasKey($computers_id, $row);
         $this->assertEquals(true, (bool) $row[$computers_id]);

         $computers_exist = $computer->getFromDB($computers_id);
         $this->assertEquals(true, (bool) $computers_exist);
         $this->assertEquals("abcdef", $computer->fields['otherserial']);
      }
   }


   /**
     * @depends testInitSessionCredentials
     * @depends testCreateItem
     */
   public function testDeleteItem($session_token, $computers_id) {
      $res = $this->doHttpRequest('DELETE', "Computer/$computers_id",
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'query' => [
                                             'force_purge'   => true]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(204, $res->getStatusCode());

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertEquals(NULL, $data);

      $computer = new Computer;
      $computers_exist = $computer->getFromDB($computers_id);
      $this->assertEquals(false, (bool) $computers_exist);
   }


   /**
     * @depends testInitSessionCredentials
     * @depends testCreateItems
     */
   public function testDeleteItems($session_token, $computers_id_collection) {
      $input    = array();
      $computer = new Computer;
      foreach($computers_id_collection as $key => $computers_id) {
         $input[] = ['id' => $computers_id['id']];
      }
      $res = $this->doHttpRequest('DELETE', "Computer/",
                                         ['headers' => [
                                             'Session-Token' => $session_token],
                                          'json' => [
                                             'input'         => $input,
                                             'force_purge'   => true]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());
      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      foreach($data as $index => $row) {
         $computers_id = $computers_id_collection[$index]['id'];
         $this->assertArrayHasKey($computers_id, $row);
         $this->assertEquals(true, (bool) $row[$computers_id]);

         $computers_exist = $computer->getFromDB($computers_id);
         $this->assertEquals(false, (bool) $computers_exist);
      }
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testKillSession($session_token) {
      // test retrieve all users
      $res = $this->doHttpRequest('GET', 'killSession/',
                                         ['headers' => [
                                             'Session-Token' => $session_token]]);
      $this->assertNotEquals(null, $res, $this->last_error);
      $this->assertEquals(200, $res->getStatusCode());

      try {
         $res = $this->doHttpRequest('GET', 'getFullSession/',
                                            ['headers' => [
                                             'Session-Token' => $session_token]]);
      } catch (ClientException $e) {
         $response = $e->getResponse();
         $this->assertEquals(401, $response->getStatusCode());
      }
   }
}
