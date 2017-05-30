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

abstract class APIBaseClass extends PHPUnit\Framework\TestCase {
   abstract protected function query($resource      = "",
                                     $params        = [],
                                     $expected_code = 200);

   abstract public function testInitSessionCredentials();

   public static function setUpBeforeClass() {
      // enable api config
      $config = new Config;
      $config->update(array('id'                              => 1,
                            'enable_api'                      => true,
                            'enable_api_login_credentials'    => true,
                            'enable_api_login_external_token' => true));
   }


   /**
    * @group  api
    * @covers API::initSession
    */
   public function testInitSessionUserToken() {
      // retrieve personnal token of TU_USER user
      $user = new User;
      $uid = getItemByTypeName('User', TU_USER, true);
      $user->getFromDB($uid);
      $token = isset($user->fields['api_token'])?$user->fields['api_token']:"";
      if (empty($token)) {
         $token = User::getToken($uid, 'api_token');
      }

      $data = $this->query('initSession',
                           ['query' => ['user_token' => $token]]);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('session_token', $data);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::changeActiveEntities
    */
   public function testChangeActiveEntities($session_token) {
      $res = $this->query('changeActiveEntities',
                          ['verb'    => 'POST',
                           'headers' => ['Session-Token' => $session_token],
                           'json'    => [
                              'entities_id'   => 'all',
                              'is_recursive'  => true]],
                          200);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::getMyEntities
    */
   public function testGetMyEntities($session_token) {
      $data = $this->query('getMyEntities',
                           ['headers' => ['Session-Token' => $session_token]]);

      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('myentities', $data); // check presence of first entity
      $this->assertArrayHasKey('id', $data['myentities'][0]); // check presence of first entity
      $this->assertEquals(0, $data['myentities'][0]['id']); // check presence of root entity
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::getActiveEntities
    */
   public function testGetActiveEntities($session_token) {
      $data = $this->query('getActiveEntities',
                           ['headers' => ['Session-Token' => $session_token]]);

      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('active_entity', $data);
      $this->assertArrayHasKey('id', $data['active_entity']);
      $this->assertArrayHasKey('active_entity_recursive', $data['active_entity']);
      $this->assertArrayHasKey('active_entities', $data['active_entity']);
      $this->assertTrue(is_array($data['active_entity']['active_entities']));
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::changeActiveProfile
    */
   public function testChangeActiveProfile($session_token) {
      $data = $this->query('changeActiveProfile',
                           ['verb'    => 'POST',
                            'headers' => ['Session-Token' => $session_token],
                            'json'    => ['profiles_id'   => 4]]);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::getMyProfiles
    */
   public function testGetMyProfiles($session_token) {
      $data = $this->query('getMyProfiles',
                           ['headers' => ['Session-Token' => $session_token]]);

      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('myprofiles', $data);  // check presence of root key
      $this->assertArrayHasKey('id', $data['myprofiles'][0]);  // check presence of id key in first entity
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::getActiveProfile
    */
   public function testGetActiveProfile($session_token) {
      $data = $this->query('getActiveProfile',
                           ['headers' => ['Session-Token' => $session_token]]);

      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('active_profile', $data);
      $this->assertArrayHasKey('id', $data['active_profile']);
      $this->assertArrayHasKey('name', $data['active_profile']);
      $this->assertArrayHasKey('interface', $data['active_profile']);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::getFullSession
    */
   public function testGetFullSession($session_token) {
      $data = $this->query('getFullSession',
                           ['headers' => ['Session-Token' => $session_token]]);

      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('session', $data);
      $this->assertArrayHasKey('glpiID', $data['session']);
      $this->assertArrayHasKey('glpiname', $data['session']);
      $this->assertArrayHasKey('glpiroot', $data['session']);
      $this->assertArrayHasKey('glpilanguage', $data['session']);
      $this->assertArrayHasKey('glpilist_limit', $data['session']);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::getMultipleItems
    */
   public function testGetMultipleItems($session_token) {
      // Get the User TU_USER and the entity in the same query
      $uid = getItemByTypeName('User', TU_USER, true);
      $eid = getItemByTypeName('Entity', '_test_root_entity', true);
      $data = $this->query('getMultipleItems',
                           ['headers' => ['Session-Token' => $session_token],
                            'query'   => [
                               'items'            => [['itemtype' => 'User',
                                                       'items_id' => $uid],
                                                      ['itemtype' => 'Entity',
                                                       'items_id' => $eid]],
                               'with_logs'        => true,
                               'expand_dropdowns' => true]]);

      unset($data['headers']);

      $this->assertEquals(true, is_array($data));
      $this->assertEquals(2, count($data));

      foreach ($data as $item) {
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
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::listSearchOptions
    */
   public function testListSearchOptions($session_token) {
      // test retrieve all users
      $data = $this->query('listSearchOptions',
                           ['itemtype' => 'Computer',
                            'headers'  => ['Session-Token' => $session_token]]);

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
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::searchItems
    */
   public function testListSearch($session_token) {
      // test retrieve all users
      $data = $this->query('search',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => [
                               'sort'          => 19,
                               'order'         => 'DESC',
                               'range'         => '0-10',
                               'forcedisplay'  => '81',
                               'rawdata'       => true]]);

      $headers = $data['headers'];
      $this->assertArrayHasKey('Accept-Range', $headers);
      $this->assertContains('User', $headers['Accept-Range'][0]);

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

      $this->checkContentRange($data, $headers);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::searchItems
    */
   public function testListSearchPartial($session_token) {
      // test retrieve partial users
      $data = $this->query('search',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => [
                              'sort'          => 19,
                              'order'         => 'DESC',
                              'range'         => '0-2',
                              'forcedisplay'  => '81',
                              'rawdata'       => true]],
                           206);

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

      $this->checkContentRange($data, $data['headers']);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::searchItems
    */
   public function testListSearchEmpty($session_token) {
      // test retrieve partial users
      $data = $this->query('search',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => [
                              'sort'          => 19,
                              'order'         => 'DESC',
                              'range'         => '0-100',
                              'forcedisplay'  => '81',
                              'rawdata'       => true,
                              'criteria'      => [
                                 [
                                    'field'      => 1,
                                    'searchtype' => 'contains',
                                    'value'      => 'nonexistent',
                                 ]
                              ]]]);

      $this->assertArrayHasKey('Accept-Range', $data['headers']);
      $this->assertContains('User', $data['headers']['Accept-Range'][0]);

      $this->assertArrayHasKey('totalcount', $data);
      $this->assertArrayHasKey('count', $data);
      $this->assertArrayHasKey('sort', $data);
      $this->assertArrayHasKey('order', $data);
      $this->assertArrayHasKey('rawdata', $data);
      $this->assertEquals(8, count($data['rawdata']));
      $this->checkEmptyContentRange($data, $data['headers']);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::searchItems
    */
   public function testSearchWithBadCriteria($session_token) {
      // test retrieve all users
      // multidimensional array of vars in query string not supported ?

      // test a non existing search option ID
      $data = $this->query('search',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => [
                              'reset'    => 'reset',
                              'criteria' => [[
                                 'field'      => '134343',
                                 'searchtype' => 'contains',
                                 'value'      => 'dsadasd',
                              ]]
                            ]],
                           400,   // 400 code expected (error, bad request)
                           'ERROR');

      // test a non numeric search option ID
      $data = $this->query('search',
                            ['itemtype' => 'User',
                             'headers'  => ['Session-Token' => $session_token],
                             'query'    => [
                              'reset'    => 'reset',
                              'criteria' => [[
                                 'field'      => '\134343',
                                 'searchtype' => 'contains',
                                 'value'      => 'dsadasd',
                              ]]
                             ]],
                           400,   // 400 code expected (error, bad request)
                           'ERROR');

      // test an incomplete criteria
      $data = $this->query('search',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => [
                             'reset'    => 'reset',
                             'criteria' => [[
                                'field'      => '134343',
                                'searchtype' => 'contains',
                             ]]
                            ]],
                         400,  // 400 code expected (error, bad request)
                         'ERROR');
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    */
   public function testBadEndpoint($session_token, $expected_code = null, $expected_symbol = null) {
      $data = $this->query('badEndpoint',
                           ['headers' => [
                            'Session-Token' => $session_token]],
                           $expected_code,
                           $expected_symbol);
   }


   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::CreateItems
    */
   public function testCreateItem($session_token) {
      $data = $this->query('createItems',
                           ['verb'     => 'POST',
                            'itemtype' => 'Computer',
                            'headers'  => ['Session-Token' => $session_token],
                            'json'     => ['input' => ['name' => "My single computer "]]],
                           201);

      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $computers_id = $data['id'];
      $this->assertEquals(true, is_numeric($computers_id));
      $this->assertEquals(true, $computers_id > 0);
      $this->assertArrayHasKey('message', $data);

      $computer = new Computer;
      $computers_exist = (bool) $computer->getFromDB($computers_id);
      $this->assertEquals(true, $computers_exist);

      // create a network port for the previous computer
      $data = $this->query('createItems',
                           ['verb'     => 'POST',
                            'itemtype' => 'NetworkPort',
                            'headers'  => ['Session-Token' => $session_token],
                            'json'     => [
                               'input' => [
                                  'instantiation_type'       => "NetworkPortEthernet",
                                  'name'                     => "test port",
                                  'logical_number'           => 1,
                                  'items_id'                 => $computers_id,
                                  'itemtype'                 => "Computer",
                                  'NetworkName_name'         => "testname",
                                  'NetworkName_fqdns_id'     => 0,
                                  // add an aditionnal key to the next array
                                  // to avoid xmlrpc losing -1 key.
                                  // see https://bugs.php.net/bug.php?id=37746
                                  'NetworkName__ipaddresses' => ['-1'                => "1.2.3.4",
                                                                 '_xmlrpc_fckng_fix' => ''],
                                  '_create_children'         => true]]],
                           201);

      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('message', $data);
      $netports_id = $data['id'];

      // try to create a new note
      $data = $this->query('createItems',
                           ['verb'     => 'POST',
                            'itemtype' => 'Notepad',
                            'headers'  => ['Session-Token' => $session_token],
                            'json'     => [
                               'input' => [
                                  'itemtype' => 'Computer',
                                  'items_id' => $computers_id,
                                  'content'  => 'note about a computer']]],
                           201);

      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('message', $data);

      return $computers_id;
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::CreateItems
    */
   public function testCreateItems($session_token) {
      $data = $this->query('createItems',
                           ['verb'     => 'POST',
                            'itemtype' => 'Computer',
                            'headers'  => ['Session-Token' => $session_token],
                            'json'     => [
                               'input' => [[
                                  'name' => "My computer 2"
                               ],[
                                  'name' => "My computer 3"
                               ],[
                                  'name' => "My computer 4"]]]],
                           201);

      $this->assertNotEquals(false, $data);
      $first_computer = $data[0];
      $secnd_computer = $data[1];
      $this->assertArrayHasKey('id', $first_computer);
      $this->assertArrayHasKey('id', $secnd_computer);
      $this->assertEquals(true, is_numeric($first_computer['id']));
      $this->assertEquals(true, is_numeric($secnd_computer['id']));
      $this->assertEquals(true, $first_computer['id'] > 0);
      $this->assertEquals(true, $secnd_computer['id'] > 0);
      $this->assertArrayHasKey('message', $data[0]);
      $this->assertArrayHasKey('message', $data[1]);

      $computer = new Computer;
      $computers_exist = $computer->getFromDB($first_computer['id']);
      $this->assertEquals(true, (bool) $computers_exist);
      $computers_exist = $computer->getFromDB($secnd_computer['id']);
      $this->assertEquals(true, (bool) $computers_exist);

      unset($data['headers']);

      return $data;
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @depends testCreateItem
    * @covers  API::getItem
    */
   public function testGetItem($session_token, $computers_id) {
      // Get the User TU_USER
      $uid = getItemByTypeName('User', TU_USER, true);
      $data = $this->query('getItem',
                           ['itemtype' => 'User',
                            'id'       => $uid,
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => [
                               'expand_dropdowns' => true,
                               'with_logs'        => true]]);

      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('entities_id', $data);
      $this->assertArrayHasKey('links', $data);
      $this->assertArrayNotHasKey('password', $data);
      $this->assertFalse(is_numeric($data['entities_id'])); // for expand_dropdowns
      $this->assertArrayHasKey('_logs', $data); // with_logs == true

      // Get user's entity
      $eid = getItemByTypeName('Entity', '_test_root_entity', true);
      $data = $this->query('getItem',
                           ['itemtype' => 'Entity',
                            'id'       => $eid,
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => ['get_hateoas' => false]]);

      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('completename', $data);
      $this->assertArrayNotHasKey('links', $data); // get_hateoas == false

      // Get the previously created 'computer 1'
      $data = $this->query('getItem',
                           ['itemtype' => 'Computer',
                            'id'       => $computers_id,
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => ['with_networkports' => true]]);

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
    * @group   api
    * @depends testInitSessionCredentials
    * @depends testCreateItem
    * @covers  API::getItem
    */
   public function testGetItemWithNotes($session_token, $computers_id) {
      // Get the previously created 'computer 1'
      $data = $this->query('getItem',
                           ['itemtype' => 'Computer',
                            'id'       => $computers_id,
                            'headers'  => ['Session-Token'     => $session_token],
                            'query'    => ['with_notes' => true]]);

      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('id', $data);
      $this->assertArrayHasKey('name', $data);
      $this->assertArrayHasKey('_notes', $data);
      $this->assertArrayHasKey('id', $data['_notes'][0]);
      $this->assertArrayHasKey('itemtype', $data['_notes'][0]);
      $this->assertArrayHasKey('items_id', $data['_notes'][0]);
      $this->assertArrayHasKey('id', $data['_notes'][0]);
      $this->assertArrayHasKey('users_id', $data['_notes'][0]);
      $this->assertArrayHasKey('content', $data['_notes'][0]);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::getItem
    */
   public function testGetItems($session_token) {
      // test retrieve all users
      $data = $this->query('getItems',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => [
                               'expand_dropdowns' => true]]);

      unset($data['headers']);
      $this->assertGreaterThanOrEqual(4, count($data));
      $this->assertArrayHasKey('id', $data[0]);
      $this->assertArrayHasKey('name', $data[0]);
      $this->assertArrayNotHasKey('password', $data[0]);
      $this->assertArrayHasKey('is_active', $data[0]);
      $this->assertFalse(is_numeric($data[0]['entities_id'])); // for expand_dropdowns

      // test retrieve partial users
      $data = $this->query('getItems',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => [
                               'range' => '0-1',
                               'expand_dropdowns' => true]],
                           206);

      unset($data['headers']);
      $this->assertEquals(2, count($data));
      $this->assertArrayHasKey('id', $data[0]);
      $this->assertArrayHasKey('name', $data[0]);
      $this->assertArrayNotHasKey('password', $data[0]);
      $this->assertArrayHasKey('is_active', $data[0]);
      $this->assertFalse(is_numeric($data[0]['entities_id'])); // for expand_dropdowns

      // test retrieve 1 user with a text filter
      $data = $this->query('getItems',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => ['searchText' => ['name' => 'gl']]]);

      unset($data['headers']);
      $this->assertEquals(1, count($data));
      $this->assertArrayHasKey('id', $data[0]);
      $this->assertArrayHasKey('name', $data[0]);
      $this->assertEquals('glpi', $data[0]['name']);

      // Test only_id param
      $data = $this->query('getItems',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => ['only_id' => true]]);

      $this->assertNotEquals(false, $data);
      unset($data['headers']);
      $this->assertGreaterThanOrEqual(4, count($data));
      $this->assertArrayHasKey('id', $data[0]);
      $this->assertArrayNotHasKey('name', $data[0]);
      $this->assertArrayNotHasKey('password', $data[0]);
      $this->assertArrayNotHasKey('is_active', $data[0]);

      // test retrieve all config
      $data = $this->query('getItems',
                           ['itemtype' => 'Config',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => ['expand_dropdowns' => true]]);

      unset($data['headers']);
      foreach ($data as $config_row) {
         $this->assertNotEquals('smtp_passwd', $config_row['name']);
         $this->assertNotEquals('proxy_passwd', $config_row['name']);
      }
   }

   /**
    * try to retrieve invalid range of users
    * We expect a http code 400
    *
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::getItem
    */
   public function testgetItemsInvalidRange($session_token) {
      $data = $this->query('getItems',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => [
                               'range' => '100-105',
                               'expand_dropdowns' => true]],
                           400,
                           'ERROR_RANGE_EXCEED_TOTAL');
   }

   /**
    * This function test https://github.com/glpi-project/glpi/issues/1103
    * A post-only user could retrieve tickets of others users when requesting itemtype
    * without first letter in uppercase
    *
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::getItem
    */
   public function testgetItemsForPostonly() {
      // init session for postonly
      $data = $this->query('initSession',
                           ['query' => [
                              'login'    => 'post-only',
                              'password' => 'postonly']]);

      // create a ticket for another user (glpi - super-admin)
      $ticket = new Ticket;
      $tickets_id = $ticket->add(array('name'                => 'test post-only',
                                       'content'             => 'test post-only',
                                       '_users_id_requester' => 2));

      // try to access this ticket with post-only
      $this->query('getItem',
                   ['itemtype' => 'Ticket',
                    'id'       => $tickets_id,
                    'headers'  => [
                        'Session-Token' => $data['session_token']]],
                   401,
                   'ERROR_RIGHT_MISSING');

      // try to access ticket list (we should get empty return)
      $data = $this->query('getItems',
                           ['itemtype' => 'Ticket',
                            'headers'  => ['Session-Token' => $data['session_token'],
                            'query'    => [
                               'expand_dropdowns' => true]]]);

      unset($data['headers']);
      $this->assertEquals(0, count($data));

      // delete ticket
      $ticket->delete(array('id' => $tickets_id), true);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @depends testCreateItem
    * @covers  API::updateItems
    */
   public function testUpdateItem($session_token, $computers_id) {
      $data = $this->query('updateItems',
                           ['itemtype' => 'Computer',
                            'verb'     => 'PUT',
                            'headers'  => ['Session-Token' => $session_token],
                            'json'     => [
                               'input' => [
                                  'id'     => $computers_id,
                                  'serial' => "abcdef"]]]);

      $this->assertNotEquals(false, $data);
      $computer = array_shift($data);
      $this->assertArrayHasKey($computers_id, $computer);
      $this->assertArrayHasKey('message', $computer);
      $this->assertEquals(true, (bool) $computer[$computers_id]);

      $computer = new Computer;
      $computers_exist = $computer->getFromDB($computers_id);
      $this->assertEquals(true, (bool) $computers_exist);
      $this->assertEquals("abcdef", $computer->fields['serial']);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @depends testCreateItems
    * @covers  API::updateItems
    */
   public function testUpdateItems($session_token, $computers_id_collection) {
      $input    = array();
      $computer = new Computer;
      foreach ($computers_id_collection as $key => $computers_id) {
         $input[] = ['id'          => $computers_id['id'],
                     'otherserial' => "abcdef"];
      }
      $data = $this->query('updateItems',
                           ['itemtype' => 'Computer',
                            'verb'     => 'PUT',
                            'headers'  => ['Session-Token' => $session_token],
                            'json'     => ['input' => $input]]);

      $this->assertNotEquals(false, $data);
      unset($data['headers']);
      foreach ($data as $index => $row) {
         $computers_id = $computers_id_collection[$index]['id'];
         $this->assertArrayHasKey($computers_id, $row);
         $this->assertArrayHasKey('message', $row);
         $this->assertEquals(true, (bool) $row[$computers_id]);

         $computers_exist = $computer->getFromDB($computers_id);
         $this->assertEquals(true, (bool) $computers_exist);
         $this->assertEquals("abcdef", $computer->fields['otherserial']);
      }
   }


   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @depends testCreateItem
    * @covers  API::deleteItems
    */
   public function testDeleteItem($session_token, $computers_id) {
      $data = $this->query('deleteItems',
                           ['itemtype' => 'Computer',
                            'id'       => $computers_id,
                            'verb'     => 'DELETE',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => ['force_purge' => "true"]]);

      $this->assertNotEquals(false, $data);
      unset($data['headers']);
      $computer = array_shift($data);
      $this->assertArrayHasKey($computers_id, $computer);
      $this->assertArrayHasKey('message', $computer);

      $computer = new Computer;
      $computers_exist = $computer->getFromDB($computers_id);
      $this->assertEquals(false, (bool) $computers_exist);
   }


   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @depends testCreateItems
    * @covers  API::deleteItems
    */
   public function testDeleteItems($session_token, $computers_id_collection) {
      $input    = array();
      $computer = new Computer;
      $lastComputer = array_pop($computers_id_collection);
      foreach ($computers_id_collection as $key => $computers_id) {
         $input[] = ['id' => $computers_id['id']];
      }
      $data = $this->query('deleteItems',
                           ['itemtype' => 'Computer',
                            'verb'     => 'DELETE',
                            'headers'  => ['Session-Token' => $session_token],
                            'json'     => [
                               'input'       => $input,
                               'force_purge' => true]]);

      $this->assertNotEquals(false, $data);
      unset($data['headers']);
      foreach ($data as $index => $row) {
         $computers_id = $computers_id_collection[$index]['id'];
         $this->assertArrayHasKey($computers_id, $row);
         $this->assertArrayHasKey('message', $row);
         $this->assertEquals(true, (bool) $row[$computers_id]);

         $computers_exist = $computer->getFromDB($computers_id);
         $this->assertEquals(false, (bool) $computers_exist);
      }
      // Test multiple delete with multi-status
      $input = [];
      $computers_id_collection = [
         ['id'  => $lastComputer['id']],
         ['id'  => $lastComputer['id'] + 1] // Non existing computer id
      ];
      foreach ($computers_id_collection as $key => $computers_id) {
         $input[] = ['id' => $computers_id['id']];
      }
      $data = $this->query('deleteItems',
                           ['itemtype' => 'Computer',
                            'verb'     => 'DELETE',
                            'headers'  => ['Session-Token' => $session_token],
                            'json'     => [
                                'input'       => $input,
                                'force_purge' => true]],
                           207);

      $this->assertNotEquals(false, $data);
      $this->assertTrue($data[1][0][$computers_id_collection[0]['id']]);
      $this->assertArrayHasKey('message', $data[1][0]);
      $this->assertFalse($data[1][1][$computers_id_collection[1]['id']]);
      $this->assertArrayHasKey('message', $data[1][1]);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    */
   public function testInjection($session_token) {
      $data = $this->query('createItems',
                           ['itemtype' => 'Computer',
                            'verb'     => 'POST',
                            'headers'  => ['Session-Token' => $session_token],
                            'json'    => [
                               'input' => [
                                  'name'        => "my computer', (SELECT `password` from `glpi_users` as `otherserial` WHERE `id`=2), '0 ' , '2016-10-26 00:00:00', '2016-10-26 00 :00 :00')#",
                                  'otherserial' => "Not hacked"]]],
                           201);

      $new_id = $data['id'];

      $computer = new Computer();
      $computer_exists = $computer->getFromDB($new_id);

      $this->assertTrue((bool)$computer_exists, 'Computer does not exists :\'(');

      $is_password = $computer->fields['otherserial'] != 'Not hacked';
      $this->assertFalse($is_password, 'Add SQL injection spotted!');

      $data = $this->query('updateItems',
                           ['itemtype' => 'Computer',
                            'verb'     => 'PUT',
                            'headers'  => ['Session-Token' => $session_token],
                             'json'    => [
                                'input' => [
                                   'id'     => $new_id,
                                   'serial' => "abcdef', `otherserial`='injected"]]]);

      $computer->getFromDB($new_id);
      $is_injected = $computer->fields['otherserial'] === 'injected';
      $this->assertFalse($is_injected, 'Update SQL injection spotted!');

      $computer = new Computer();
      $computer->delete(['id' => $new_id], true);
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    */
   public function testProtectedConfigSettings($session_token) {
      $sensitiveSettings = array(
            'proxy_passwd',
            'smtp_passwd',
      );

      // set a non empty value to the sessionts to check
      foreach ($sensitiveSettings as $name) {
         Config::setConfigurationValues('core', array($name => 'not_empty_password'));
         $value = Config::getConfigurationValues('core', array($name));
         $this->assertArrayHasKey($name, $value);
         $this->assertNotEmpty($value[$name]);
      }

      $where = "'" . implode("', '", $sensitiveSettings) . "'";
      $config = new config();
      $rows = $config->find("`context`='core' AND `name` IN ($where)");
      $this->assertEquals(count($sensitiveSettings), count($rows));

      // Check the value is not retrieved for sensitive settings
      foreach ($rows as $row) {
         $data = $this->query('getItem',
                              ['itemtype' => 'Config',
                               'id'       => $row['id'],
                               'headers' => ['Session-Token' => $session_token]]);
         $this->assertArrayNotHasKey('value', $data);
      }

      // Check an other setting is disclosed (when not empty)
      $config = new Config();
      $config->getFromDBByQuery("WHERE `context`='core' AND `name`='admin_email'");
      $data = $this->query('getItem',
                           ['itemtype' => 'Config',
                            'id'       => $config->getID(),
                            'headers' => ['Session-Token' => $session_token]]);

      $this->assertNotEquals('', $data['value']);

      // Check a search does not disclose sensitive values
      $criteria = array();
      $queryString = "";
      foreach ($rows as $row) {
         $queryString = "&criteria[0][link]=or&criteria[0][field]=1&criteria[0][searchtype]=equals&criteria[0][value]=".$row['name'];
      }

      $data = $this->query('search',
                           ['itemtype' => 'Config',
                            'headers'  => ['Session-Token' => $session_token],
                            'query'    => []],
                           206);
      foreach ($data['data'] as $row) {
         foreach ($row as $col) {
              $this->assertNotEquals($col, 'not_empty_password');
         }
      }
   }

   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::getGlpiConfig
    */
   public function testGetGlpiConfig($session_token) {
      $data = $this->query('getGlpiConfig',
                           ['headers'  => ['Session-Token' => $session_token]]);

      // Test a disclosed data
      $this->assertArrayHasKey('cfg_glpi', $data);
      $this->assertArrayHasKey('infocom_types', $data['cfg_glpi']);

      // Test undisclosed data are actually not disclosed
      $this->assertGreaterThan(0, count(Config::$undisclosedFields));
      foreach (Config::$undisclosedFields as $key) {
         $this->assertArrayNotHasKey($key, $data['cfg_glpi']);
      }

   }


   /**
    * @group   api
    * @depends testInitSessionCredentials
    * @covers  API::killSession
    */
   public function testKillSession($session_token) {
      // test retrieve all users
      $res = $this->query('killSession',
                          ['headers' => ['Session-Token' => $session_token]]);
      $res = $this->query('getFullSession',
                          ['headers' => ['Session-Token' => $session_token]],
                          401,
                          'ERROR_SESSION_TOKEN_INVALID');
   }

   /**
    * Check consistency of Content-Range header
    *
    * @param array $data
    * @param array $headers
    */
   protected function checkContentRange($data, $headers) {
      $this->assertLessThanOrEqual($data['totalcount'], $data['count']);
      $this->assertArrayHasKey('Content-Range', $headers);
      $expectedContentRange = '0-'.($data['count'] - 1).'/'.$data['totalcount'];
      $this->assertEquals($expectedContentRange, $headers['Content-Range'][0]);
   }

   /**
    * Check consistency of Content-Range header
    *
    * @param array $data
    * @param array $headers
    */
   protected function checkEmptyContentRange($data, $headers) {
      $this->assertLessThanOrEqual($data['totalcount'], $data['count']);
      $this->assertEquals(0, $data['totalcount']);
      $this->assertArrayNotHasKey('Content-Range', $headers);
   }
}
