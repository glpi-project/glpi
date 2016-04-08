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

class APIRestTest extends PHPUnit_Framework_TestCase {
   protected $http_client;

   protected function setUp() {
      global $CFG_GLPI;
      $this->http_client = new GuzzleHttp\Client([
         'base_uri'    => trim($CFG_GLPI['url_base'], "/")."/api/"
      ]);
   }

   public function testInitSessionCredentials() {
      $res = $this->http_client->request('GET', 'initSession/',
                                         ['query' => [
                                             'login'    => 'glpi',
                                             'password' => 'glpi'
                                         ]]);


      $this->assertEquals(200, $res->getStatusCode());
      $this->assertContains( "application/json; charset=UTF-8", $res->getHeader('content-type') );

      $data = json_decode($res->getBody(), true);
      $this->assertArrayHasKey('session_token', $data);
      return $data['session_token'];
   }

   public function testInitSessionPersonnalToken() {
      // retrieve personnal token of 'glpi' user
      $user = new User;
      $user->getFromDB(2);
      $token = isset($user->fields['personnal_token'])?$user->fields['personnal_token']:"";
      if (empty($token)) {
         $token = User::getPersonalToken(2);
      }

      $res = $this->http_client->request('GET', 'initSession/',
                                         ['query' => [
                                             'api_key' => $token
                                         ]]);


      $this->assertEquals(200, $res->getStatusCode());

      $data = json_decode($res->getBody(), true);
      $this->assertArrayHasKey('session_token', $data);
   }

   /**
     * @depends testInitSessionCredentials
     */
   public function testChangeActiveEntities($session_token) {
      $res = $this->http_client->request('POST', 'changeActiveEntities/',
                                         ['json' => [
                                             'session_token' => $session_token,
                                             'entities_id'   => 'all',
                                             'is_recursive'  => true
                                         ]]);
      $this->assertEquals(200, $res->getStatusCode());
   }

   /**
     * @depends testInitSessionCredentials
     */
   public function testGetMyEntities($session_token) {
      $res = $this->http_client->request('GET', 'getMyEntities/',
                                         ['query' => [
                                             'session_token' => $session_token
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = json_decode($res->getBody(), true);
      $this->assertArrayHasKey(0, $data); // check presence of root entity
   }

   /**
     * @depends testInitSessionCredentials
     */
   public function testGetActiveEntities($session_token) {
      $res = $this->http_client->request('GET', 'getActiveEntities/',
                                         ['query' => [
                                             'session_token' => $session_token
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = json_decode($res->getBody(), true);
      $this->assertArrayHasKey('active_entity', $data);
      $this->assertArrayHasKey('active_entity_recursive', $data);
      $this->assertArrayHasKey('active_entities', $data);
      $this->assertTrue(is_array($data['active_entities']), $data);
   }

   /**
     * @depends testInitSessionCredentials
     */
   public function testChangeActiveProfile($session_token) {
      $res = $this->http_client->request('POST', 'changeActiveProfile/',
                                         ['json' => [
                                             'session_token' => $session_token,
                                             'profiles_id'   => 4
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());
   }

   /**
     * @depends testInitSessionCredentials
     */
   public function testGetMyProfiles($session_token) {
      $res = $this->http_client->request('GET', 'getMyProfiles/',
                                         ['query' => [
                                             'session_token' => $session_token
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = json_decode($res->getBody(), true);
      $this->assertArrayHasKey(4, $data);  // check presence of super-admin profile
   }

   /**
     * @depends testInitSessionCredentials
     */
   public function testGetActiveProfile($session_token) {
      $res = $this->http_client->request('GET', 'getActiveProfile/',
                                         ['query' => [
                                             'session_token' => $session_token
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = json_decode($res->getBody(), true);
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('interface', $data);
   }

   /**
     * @depends testInitSessionCredentials
     */
   public function testGetFullSession($session_token) {
      $res = $this->http_client->request('GET', 'getFullSession/',
                                         ['query' => [
                                             'session_token' => $session_token
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = json_decode($res->getBody(), true);
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
      $res = $this->http_client->request('GET', 'User/2/',
                                         ['query' => [
                                             'session_token'    => $session_token,
                                             'expand_dropdowns' => true,
                                             'with_logs'        => true,
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = json_decode($res->getBody(), true);
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('entities_id', $data);
      $this->assertArrayHasKey('links', $data);
      $this->assertFalse(is_numeric($data['entities_id'])); // for expand_dropdowns
      $this->assertArrayHasKey('_logs', $data); // with_logs == true

      // Get the root-entity
      $res = $this->http_client->request('GET', 'Entity/0',
                                         ['query' => [
                                             'session_token' => $session_token,
                                             'get_hateoas'   => false,
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = json_decode($res->getBody(), true);
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
      $res = $this->http_client->request('GET', 'User/',
                                         ['query' => [
                                             'session_token'    => $session_token,
                                             'expand_dropdowns' => true
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());
      $data = json_decode($res->getBody(), true);

      $this->assertGreaterThanOrEqual(4, count($data));
      $this->assertArrayHasKey('id', $data[0]);
      $this->assertArrayHasKey('name', $data[0]);
      $this->assertArrayHasKey('password', $data[0]);
      $this->assertArrayHasKey('is_active', $data[0]);
      $this->assertFalse(is_numeric($data[0]['entities_id'])); // for expand_dropdowns


      // Test only_id param
      $res = $this->http_client->request('GET', 'User/',
                                         ['query' => [
                                             'session_token' => $session_token,
                                             'only_id'       => true
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = json_decode($res->getBody(), true);
      $this->assertGreaterThanOrEqual(4, count($data));
      $this->assertArrayHasKey('id', $data[0]);
      $this->assertArrayNotHasKey('name', $data[0]);
      $this->assertArrayNotHasKey('password', $data[0]);
      $this->assertArrayNotHasKey('is_active', $data[0]);
   }

   /**
     * @depends testInitSessionCredentials
     */
   public function testListSearchOptions($session_token) {
      // test retrieve all users
      $res = $this->http_client->request('GET', 'listSearchOptions/Computer/',
                                         ['query' => [
                                             'session_token' => $session_token
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());

      $data = json_decode($res->getBody(), true);
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
      $res = $this->http_client->request('GET', 'search/User/',
                                         ['query' => [
                                             'session_token' => $session_token,
                                             'sort'          => 19,
                                             'order'         => 'DESC',
                                             'range'         => '10-15',
                                             'forcedisplay'  => '81',
                                             'rawdata'       => true
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());

      $headers = $res->getHeaders();
      $this->assertArrayHasKey('Accept-Range', $headers);
      $this->assertContains('User', $headers['Accept-Range'][0]);
      $this->assertArrayHasKey('Content-Range', $headers);
      $this->assertEquals('10-15/5', $headers['Content-Range'][0]);

      $data = json_decode($res->getBody(), true);
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
      $res = $this->http_client->request('GET', 'badEndpoint/',
                                         ['query' => [
                                             'session_token' => $session_token],
                                          'http_errors' => false]);
      $this->assertEquals(400, $res->getStatusCode());

      $res = $this->http_client->request('GET', 'Entity/0/badEndpoint/',
                                         ['query' => [
                                             'session_token' => $session_token],
                                          'http_errors' => false]);
      $this->assertEquals(400, $res->getStatusCode());
   }


   // TODO addItem
   // TODO updateItem
   // TODO deleteItem

   /**
     * @depends testInitSessionCredentials
     */
   public function testKillSession($session_token) {
      // test retrieve all users
      $res = $this->http_client->request('GET', 'killSession/',
                                         ['query' => [
                                             'session_token' => $session_token
                                          ]]);
      $this->assertEquals(200, $res->getStatusCode());

      $res = $this->http_client->request('GET', 'getFullSession/',
                                         ['query' => [
                                             'session_token' => $session_token],
                                          'http_errors' => false]);
      $this->assertEquals(401, $res->getStatusCode());
   }
}