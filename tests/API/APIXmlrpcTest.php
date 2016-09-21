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

class APIXmlrpcTest extends PHPUnit_Framework_TestCase {
   protected $http_client;
   protected $base_uri = "";

   protected function setUp() {
      global $CFG_GLPI;

      $this->http_client = new GuzzleHttp\Client();
      $this->base_uri    = trim($CFG_GLPI['url_base'], "/")."/apixmlrpc.php";
   }

   protected function doHttpRequest($resource = "", $params = array()) {
      $headers = array("Content-Type" => "text/xml");
      $request = xmlrpc_encode_request($resource, $params);
      return $this->http_client->post($this->base_uri,['body'    => $request,
                                                       'headers' => $headers]);
   }


   public function testInitSessionCredentials() {
      $res = $this->doHttpRequest('initSession', ['login'    => 'glpi',
                                                  'password' => 'glpi']);

      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
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

      $res = $this->doHttpRequest('initSession', ['user_token' => $token]);

      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('session_token', $data);
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testChangeActiveEntities($session_token) {
      $res = $this->doHttpRequest('changeActiveEntities', ['session_token' => $session_token,
                                                           'entities_id'   => 'all',
                                                           'is_recursive'  => true]);
      $this->assertEquals(200, $res->getStatusCode());
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetMyEntities($session_token) {
      $res = $this->doHttpRequest('getMyEntities', ['session_token' => $session_token]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
      $this->assertNotEquals(false, $data);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('myentities', $data); // check presence of first entity
      $this->assertArrayHasKey('id', $data['myentities'][0]); // check presence of first entity
      $this->assertEquals(0, $data['myentities'][0]['id']); // check presence of root entity
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetActiveEntities($session_token) {
      $res = $this->doHttpRequest('getActiveEntities', ['session_token' => $session_token]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
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
      $res = $this->doHttpRequest('changeActiveProfile', ['session_token' => $session_token,
                                                          'profiles_id'   => 4]);
      $this->assertEquals(200, $res->getStatusCode());
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetMyProfiles($session_token) {
      $res = $this->doHttpRequest('getMyProfiles', ['session_token' => $session_token]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
      $this->assertNotEquals(false, $data);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('myprofiles', $data);  // check presence of root key
      $this->assertArrayHasKey('id', $data['myprofiles'][0]);  // check presence of id key in first entity
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetActiveProfile($session_token) {
      $res = $this->doHttpRequest('getActiveProfile', ['session_token' => $session_token]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('interface', $data);
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetFullSession($session_token) {
      $res = $this->doHttpRequest('getFullSession', ['session_token' => $session_token]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('glpiID', $data);
      $this->assertArrayHasKey('glpiname', $data);
      $this->assertArrayHasKey('glpiroot', $data);
      $this->assertArrayHasKey('glpilanguage', $data);
      $this->assertArrayHasKey('glpilist_limit', $data);
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetItem($session_token) {
      // Get the User 'glpi'
      $res = $this->doHttpRequest('getItem', ['session_token'    => $session_token,
                                              'itemtype'         => 'User',
                                              'id'               => 2,
                                              'expand_dropdowns' => true,
                                              'with_logs'        => true]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('entities_id', $data);
      $this->assertArrayHasKey('links', $data);
      $this->assertFalse(is_numeric($data['entities_id'])); // for expand_dropdowns
      $this->assertArrayHasKey('_logs', $data); // with_logs == true

      // Get the root-entity
      $res = $this->doHttpRequest('getItem', ['session_token' => $session_token,
                                              'itemtype'      => 'Entity',
                                              'id'            => 0,
                                              'get_hateoas'   => false]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('completename', $data);
      $this->assertArrayNotHasKey('links', $data); // get_hateoas == false
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetItems($session_token) {
      // test retrieve all users
      $res = $this->doHttpRequest('getItems', ['session_token'    => $session_token,
                                               'itemtype'         => 'User',
                                               'expand_dropdowns' => true]);
      $this->assertEquals(200, $res->getStatusCode());
      $data = xmlrpc_decode($res->getBody());

      $this->assertGreaterThanOrEqual(4, count($data));
      $this->assertArrayHasKey('id', $data[0]);
      $this->assertArrayHasKey('name', $data[0]);
      $this->assertArrayHasKey('password', $data[0]);
      $this->assertArrayHasKey('is_active', $data[0]);
      $this->assertFalse(is_numeric($data[0]['entities_id'])); // for expand_dropdowns


      // Test only_id param
      $res = $this->doHttpRequest('getItems', ['session_token' => $session_token,
                                               'itemtype'      => 'User',
                                               'only_id'       => true]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
      $this->assertNotEquals(false, $data);
      $this->assertGreaterThanOrEqual(4, count($data));
      $this->assertArrayHasKey('id', $data[0]);
      $this->assertArrayNotHasKey('name', $data[0]);
      $this->assertArrayNotHasKey('password', $data[0]);
      $this->assertArrayNotHasKey('is_active', $data[0]);
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testGetMultipleItems($session_token) {
      // Get the User 'glpi' and the root entity in the same query
      $res = $this->doHttpRequest('getMultipleItems', ['session_token'    => $session_token,
                                                       'items'            => [['itemtype' => 'User',
                                                                               'items_id' => 2],
                                                                              ['itemtype' => 'Entity',
                                                                               'items_id' => 0]],
                                                       'expand_dropdowns' => true,
                                                       'with_logs'        => true]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());

      $this->assertEquals(true, is_array($data));
      $this->assertEquals(2, count($data));

      foreach($data as $item) {
         $this->assertArrayHasKey('id', $item);
         $this->assertArrayHasKey('name', $item);
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
      $res = $this->doHttpRequest('listSearchOptions', ['session_token' => $session_token,
                                                        'itemtype'      => 'Computer']);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
      $this->assertNotEquals(false, $data);
      $this->assertGreaterThanOrEqual(128, count($data));
      $this->assertEquals('Name', $data[1]['name']);
      $this->assertEquals('glpi_computers', $data[1]['table']);
      $this->assertEquals('name', $data[1]['field']);
      $this->assertEquals('itemlink', $data[1]['datatype']);
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testListSearch($session_token) {
      // test retrieve all users
      $res = $this->doHttpRequest('search', ['session_token' => $session_token,
                                             'itemtype'      => 'User',
                                             'sort'          => 19,
                                             'order'         => 'DESC',
                                             'range'         => '0-2',
                                             'forcedisplay'  => '81',
                                             'rawdata'       => true]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
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
         $res = $this->doHttpRequest('badEndpoint', ['session_token' => $session_token]);
      } catch (ClientException $e) {
         $response = $e->getResponse();
         $this->assertEquals(405, $response->getStatusCode());
      }
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testCreateItem($session_token) {
      $res = $this->doHttpRequest('createItems', ['session_token' => $session_token,
                                                  'itemtype'      => 'Computer',
                                                  'input'         => ['name' => "My computer 1"]]);
      $this->assertEquals(201, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $id = $data['id'];
      $this->assertEquals(true, is_numeric($id));
      $this->assertEquals(true, $id > 0);

      $computer = new Computer;
      $computers_exist = $computer->getFromDB($id);
      $this->assertEquals(true, (bool) $computers_exist);

      return $id;
   }


   /**
     * @depends testInitSessionCredentials
     */
   public function testCreateItems($session_token) {
      $res = $this->doHttpRequest('createItems', ['session_token' => $session_token,
                                                  'itemtype'      => 'Computer',
                                                  'input'         => [[
                                                     'name' => "My computer 2"
                                                  ],[
                                                     'name' => "My computer 3"
                                                  ]]]);
      $this->assertEquals(201, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey(0, $data);
      $this->assertArrayHasKey(1, $data);
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
   public function testUpdateItem($session_token, $computers_id) {
      $res = $this->doHttpRequest('updateItems', ['session_token' => $session_token,
                                                  'itemtype'      => 'Computer',
                                                  'input'         => [
                                                     'id'     => $computers_id,
                                                     'serial' => "abcdef"
                                                  ]]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
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
     * @depends testCreateItems
     */
   public function testUpdateItems($session_token, $computers_id_collection) {
      $input    = array();
      $computer = new Computer;
      foreach($computers_id_collection as $key => $computers_id) {
         $input[] = ['id'          => $computers_id['id'],
                     'otherserial' => "abcdef"];
      }
      $res = $this->doHttpRequest('updateItems', ['session_token' => $session_token,
                                                  'itemtype'      => 'Computer',
                                                  'input'         => $input]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
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
      $res = $this->doHttpRequest('deleteItems', ['session_token' => $session_token,
                                                  'itemtype'      => 'Computer',
                                                  'id'            => $computers_id,
                                                  'force_purge'   => true]);
      $this->assertEquals(204, $res->getStatusCode());

      $data = xmlrpc_decode($res->getBody());
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
      $res = $this->doHttpRequest('deleteItems', ['session_token' => $session_token,
                                                  'itemtype'      => 'Computer',
                                                  'input'         => $input,
                                                  'force_purge'   => true]);
      $this->assertEquals(200, $res->getStatusCode());
      $data = xmlrpc_decode($res->getBody());
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
      $res = $this->doHttpRequest('killSession', ['session_token' => $session_token]);
      $this->assertEquals(200, $res->getStatusCode());

      try {
         $res = $this->doHttpRequest('getFullSession', ['session_token' => $session_token]);
      } catch (ClientException $e) {
         $response = $e->getResponse();
         $this->assertEquals(401, $response->getStatusCode());
      }
   }
}
