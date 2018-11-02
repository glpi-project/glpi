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

abstract class APIBaseClass extends \atoum {
   protected $session_token;
   protected $http_client;
   protected $base_uri = "";
   protected $last_error;

   abstract protected function query($resource = "",
                                     $params = [],
                                     $expected_code = 200);

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->initSessionCredentials();
   }

   abstract public function initSessionCredentials();

   public function setUp() {
      parent::setUp();
      // enable api config
      $config = new Config;
      $config->update(['id'                              => 1,
                            'enable_api'                      => true,
                            'enable_api_login_credentials'    => true,
                            'enable_api_login_external_token' => true]);
   }


   /**
    * @tags   api
    * @covers API::initSession
    */
   public function testInitSessionUserToken() {
      // retrieve personnal token of TU_USER user
      $user = new User;
      $uid = getItemByTypeName('User', TU_USER, true);
      $user->getFromDB($uid);
      $token = isset($user->fields['api_token'])?$user->fields['api_token']:"";
      if (empty($token)) {
         $token = $user->getAuthToken('api_token');
      }

      $data = $this->query('initSession',
                           ['query' => ['user_token' => $token]]);
      $this->variable($data)->isNotFalse();
      $this->array($data)->hasKey('session_token');
   }

   /**
    * @tags   api
    * @covers API::initSession
    */
   public function testAppToken() {
      $apiclient = new APIClient;
      $this->integer(
         (int)$apiclient->add([
            'name'             => 'test app token',
            'is_active'        => 1,
            'ipv4_range_start' => 2130706433,
            'ipv4_range_end'   => 2130706433,
            '_reset_app_token' => true,
         ])
      )->isGreaterThan(0);

      $app_token = $apiclient->fields['app_token'];
      $this->string($app_token)->isNotEmpty()->hasLength(40);

      // test valid app token -> expect ok session
      $data = $this->query('initSession',
            ['query' => [
                  'login'     => TU_USER,
                  'password'  => TU_PASS,
                  'app_token' => $app_token]]);
      $this->variable($data)->isNotFalse();
      $this->array($data)->hasKey('session_token');

      // test invalid app token -> expect error 400 and a specific code
      $data = $this->query('initSession',
            ['query' => [
                  'login'     => TU_USER,
                  'password'  => TU_PASS,
                  'app_token' => "test_invalid_token"]],
            400, 'ERROR_WRONG_APP_TOKEN_PARAMETER');
   }

   /**
    * @tags    api
    * @covers  API::changeActiveEntities
    */
   public function testChangeActiveEntities() {
      $res = $this->query('changeActiveEntities',
                          ['verb'    => 'POST',
                           'headers' => ['Session-Token' => $this->session_token],
                           'json'    => [
                              'entities_id'   => 'all',
                              'is_recursive'  => true]],
                          200);
   }

   /**
    * @tags    api
    * @covers  API::getMyEntities
    */
   public function testGetMyEntities() {
      $data = $this->query('getMyEntities',
                           ['headers' => ['Session-Token' => $this->session_token]]);

      $this->variable($data)->isNotFalse();
      $this->array($data)
         ->hasKey('myentities')
         ->array['myentities']
            ->array[0] // check presence of first entity
               ->variable['id']
                  ->isEqualTo(0); // check presence of root entity
   }

   /**
    * @tags    api
    * @covers  API::getActiveEntities
    */
   public function testGetActiveEntities() {
      $data = $this->query('getActiveEntities',
                           ['headers' => ['Session-Token' => $this->session_token]]);

      $this->variable($data)->isNotFalse();
      $this->array($data)
         ->array['active_entity'];

      $this->array($data['active_entity'])
         ->hasKey('id')
         ->hasKey('active_entity_recursive')
         ->hasKey('active_entities')
         ->array['active_entities'];
   }

   /**
    * @tags    api
    * @covers  API::changeActiveProfile
    */
   public function testChangeActiveProfile() {
      // test change to an existing and available profile
      $data = $this->query('changeActiveProfile',
                           ['verb'    => 'POST',
                            'headers' => ['Session-Token' => $this->session_token],
                            'json'    => ['profiles_id'   => 4]]);

      // test change to a non existing profile
      $data = $this->query('changeActiveProfile',
                           ['verb'    => 'POST',
                            'headers' => ['Session-Token' => $this->session_token],
                            'json'    => ['profiles_id'   => 9999]],
                           404,
                           'ERROR_ITEM_NOT_FOUND');

      // test a bad request
      $data = $this->query('changeActiveProfile',
                           ['verb'    => 'POST',
                            'headers' => ['Session-Token' => $this->session_token],
                            'json'    => ['something_bad' => 4]],
                           400,
                           'ERROR');
   }

   /**
    * @tags    api
    * @covers  API::getMyProfiles
    */
   public function testGetMyProfiles() {
      $data = $this->query('getMyProfiles',
                           ['headers' => ['Session-Token' => $this->session_token]]);

      $this->variable($data)->isNotFalse();
      $this->array($data)
         ->hasKey('myprofiles'); // check presence of root key
      $this->array($data['myprofiles'][0])
         ->hasKey('id'); // check presence of id key in first profile
   }

   /**
    * @tags    api
    * @covers  API::getActiveProfile
    */
   public function testGetActiveProfile() {
      $data = $this->query('getActiveProfile',
                           ['headers' => ['Session-Token' => $this->session_token]]);

      $this->variable($data)->isNotFalse();
      $this->array($data)
         ->hasKey('active_profile');
      $this->array($data['active_profile'])
         ->hasKey('id')
         ->hasKey('name')
         ->hasKey('interface');
   }

   /**
    * @tags    api
    * @covers  API::getFullSession
    */
   public function testGetFullSession() {
      $data = $this->query('getFullSession',
                           ['headers' => ['Session-Token' => $this->session_token]]);

      $this->variable($data)->isNotFalse();
      $this->array($data)->hasKey('session');

      $this->array($data['session'])
         ->hasKey('glpiID')
         ->hasKey('glpiname')
         ->hasKey('glpiroot')
         ->hasKey('glpilanguage')
         ->hasKey('glpilist_limit');
   }

   /**
    * @tags    api
    * @covers  API::getMultipleItems
    */
   public function testGetMultipleItems() {
      // Get the User TU_USER and the entity in the same query
      $uid = getItemByTypeName('User', TU_USER, true);
      $eid = getItemByTypeName('Entity', '_test_root_entity', true);
      $data = $this->query('getMultipleItems',
                           ['headers' => ['Session-Token' => $this->session_token],
                            'query'   => [
                               'items'            => [['itemtype' => 'User',
                                                       'items_id' => $uid],
                                                      ['itemtype' => 'Entity',
                                                       'items_id' => $eid]],
                               'with_logs'        => true,
                               'expand_dropdowns' => true]]);

      unset($data['headers']);

      $this->array($data)
         ->hasSize(2);

      foreach ($data as $item) {
         $this->array($item)
            ->hasKey('id')
            ->hasKey('name')
            ->hasKey('entities_id')
            ->hasKey('links')
            ->hasKey('_logs') // with_logs == true
            ->notHasKey('password');
         $this->boolean(is_numeric($item['entities_id']))->isFalse(); // for expand_dropdowns
      }
   }

   /**
    * @tags    api
    * @covers  API::listSearchOptions
    */
   public function testListSearchOptions() {
      // test retrieve all users
      $data = $this->query('listSearchOptions',
                           ['itemtype' => 'Computer',
                            'headers'  => ['Session-Token' => $this->session_token]]);

      $this->variable($data)->isNotFalse();
      $this->array($data)
         ->size->isGreaterThanOrEqualTo(128);

      $this->array($data[1])
         ->string['name']->isIdenticalTo('Name')
         ->string['table']->isIdenticalTo('glpi_computers')
         ->string['field']->isIdenticalTo('name')
         ->array['available_searchtypes'];

      $this->array($data[1]['available_searchtypes'])
         ->isIdenticalTo(['contains', 'notcontains', 'equals', 'notequals']);
   }

   /**
    * @tags    api
    * @covers  API::searchItems
    */
   public function testListSearch() {
      // test retrieve all users
      $data = $this->query('search',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => [
                               'sort'          => 19,
                               'order'         => 'DESC',
                               'range'         => '0-10',
                               'forcedisplay'  => '81',
                               'rawdata'       => true]]);

      $this->array($data)
         ->hasKey('headers')
         ->hasKey('totalcount')
         ->hasKey('count')
         ->hasKey('sort')
         ->hasKey('order')
         ->hasKey('rawdata');

      $headers = $data['headers'];

      $this->array($data['headers'])
         ->hasKey('Accept-Range');

      $this->string($headers['Accept-Range'][0])
         ->startWith('User');

      $this->array($data['rawdata'])
         ->hasSize(9);

      $first_user = array_shift($data['data']);
      $second_user = array_shift($data['data']);

      $this->array($first_user)->hasKey(81);
      $this->array($second_user)->hasKey(81);

      $first_user_date_mod = strtotime($first_user[19]);
      $second_user_date_mod = strtotime($second_user[19]);
      $this->integer($second_user_date_mod)->isLessThanOrEqualTo($first_user_date_mod);

      $this->checkContentRange($data, $headers);
   }

   /**
    * @tags    api
    * @covers  API::searchItems
    */
   public function testListSearchPartial() {
      // test retrieve partial users
      $data = $this->query('search',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => [
                              'sort'          => 19,
                              'order'         => 'DESC',
                              'range'         => '0-2',
                              'forcedisplay'  => '81',
                              'rawdata'       => true]],
                           206);

      $this->variable($data)->isNotFalse();

      $this->array($data)
         ->hasKey('totalcount')
         ->hasKey('count')
         ->hasKey('sort')
         ->hasKey('order')
         ->hasKey('rawdata');

      $this->array($data['rawdata'])
         ->hasSize(9);

      $first_user = array_shift($data['data']);
      $second_user = array_shift($data['data']);
      $this->array($first_user)->hasKey(81);
      $this->array($second_user)->hasKey(81);
      $first_user_date_mod = strtotime($first_user[19]);
      $second_user_date_mod = strtotime($second_user[19]);
      $this->integer($second_user_date_mod)->isLessThanOrEqualTo($first_user_date_mod);

      $this->checkContentRange($data, $data['headers']);
   }

   /**
    * @tags    api
    * @covers  API::searchItems
    */
   public function testListSearchEmpty() {
      // test retrieve partial users
      $data = $this->query('search',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $this->session_token],
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

      $this->variable($data)->isNotFalse();

      $this->array($data)
         ->hasKey('headers')
         ->hasKey('totalcount')
         ->hasKey('count')
         ->hasKey('sort')
         ->hasKey('order')
         ->hasKey('rawdata');

      $this->array($data['headers'])
         ->hasKey('Accept-Range');

      $this->string($data['headers']['Accept-Range'][0])
         ->startWith('User');

      $this->array($data['rawdata'])
         ->hasSize(9);
      $this->checkEmptyContentRange($data, $data['headers']);
   }

   /**
    * @tags    api
    * @covers  API::searchItems
    */
   public function testSearchWithBadCriteria() {
      // test retrieve all users
      // multidimensional array of vars in query string not supported ?

      // test a non existing search option ID
      $data = $this->query('search',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $this->session_token],
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
                             'headers'  => ['Session-Token' => $this->session_token],
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
                            'headers'  => ['Session-Token' => $this->session_token],
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
    * @tags    api
    */
   protected function badEndpoint($expected_code = null, $expected_symbol = null) {
      $data = $this->query('badEndpoint',
                           ['headers' => [
                            'Session-Token' => $this->session_token]],
                           $expected_code,
                           $expected_symbol);
   }

   /**
    * Create a computer
    *
    * @return Computer
    */
   protected function createComputer() {
      $data = $this->query('createItems',
                           ['verb'     => 'POST',
                            'itemtype' => 'Computer',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'json'     => ['input' => ['name' => "My single computer "]]],
                           201);

      $this->variable($data)
         ->isNotFalse();

      $this->array($data)
         ->hasKey('id')
         ->hasKey('message');

      $computers_id = $data['id'];
      $this->boolean(is_numeric($computers_id))->isTrue();
      $this->integer((int)$computers_id)->isGreaterThanOrEqualTo(0);

      $computer = new Computer;
      $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();
      return $computer;
   }

   /**
    * Create a network port
    *
    * @param integer $computers_id Computer ID
    *
    * @return void
    */
   protected function createNetworkPort($computers_id) {
      $data = $this->query('createItems',
                           ['verb'     => 'POST',
                            'itemtype' => 'NetworkPort',
                            'headers'  => ['Session-Token' => $this->session_token],
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

      $this->variable($data)->isNotFalse();

      $this->array($data)
         ->hasKey('id')
         ->hasKey('message');
   }

   /**
    * Create a note
    *
    * @param integer $computers_id Computer ID
    *
    * @return void
    */
   protected function createNote($computers_id) {
      $data = $this->query('createItems',
                           ['verb'     => 'POST',
                            'itemtype' => 'Notepad',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'json'     => [
                               'input' => [
                                  'itemtype' => 'Computer',
                                  'items_id' => $computers_id,
                                  'content'  => 'note about a computer']]],
                           201);

      $this->variable($data)->isNotFalse();

      $this->array($data)
         ->hasKey('id')
         ->hasKey('message');
   }

   /**
    * @tags    api
    * @covers  API::CreateItems
    */
   public function testCreateItem() {
      $computer = $this->createComputer();
      $computers_id = $computer->getID();

      // create a network port for the previous computer
      $this->createNetworkPort($computers_id);

      // try to create a new note
      $this->createNote($computers_id);
   }

   /**
    * @tags    api
    * @covers  API::CreateItems
    */
   public function testCreateItems() {
      $data = $this->query('createItems',
                           ['verb'     => 'POST',
                            'itemtype' => 'Computer',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'json'     => [
                               'input' => [[
                                  'name' => "My computer 2"
                               ],[
                                  'name' => "My computer 3"
                               ],[
                                  'name' => "My computer 4"]]]],
                           201);

      $this->variable($data)->isNotFalse();

      $first_computer = $data[0];
      $second_computer = $data[1];

      $this->array($first_computer)
         ->hasKey('id')
         ->hasKey('message');
      $this->array($second_computer)
         ->hasKey('id')
         ->hasKey('message');

      $this->boolean(is_numeric($first_computer['id']))->isTrue();
      $this->boolean(is_numeric($second_computer['id']))->isTrue();

      $this->integer((int)$first_computer['id'])->isGreaterThanOrEqualTo(0);
      $this->integer((int)$second_computer['id'])->isGreaterThanOrEqualTo(0);

      $computer = new Computer;
      $this->boolean((bool)$computer->getFromDB($first_computer['id']))->isTrue();
      $this->boolean((bool)$computer->getFromDB($second_computer['id']))->isTrue();

      unset($data['headers']);
      return $data;
   }

   /**
    * @tags    apit
    * @covers  API::getItem
    */
   public function testGetItem() {
      $computer = $this->createComputer();
      $computers_id = $computer->getID();

      // create a network port for the previous computer
      $this->createNetworkPort($computers_id);

      // Get the User TU_USER
      $uid = getItemByTypeName('User', TU_USER, true);
      $data = $this->query('getItem',
                           ['itemtype' => 'User',
                            'id'       => $uid,
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => [
                               'expand_dropdowns' => true,
                               'with_logs'        => true]]);

      $this->variable($data)->isNotFalse();
      $this->array($data)
         ->hasKey('id')
         ->hasKey('name')
         ->hasKey('entities_id')
         ->hasKey('links')
         ->hasKey('_logs') // with_logs == true
         ->notHasKey('password');
      $this->boolean(is_numeric($data['entities_id']))->isFalse(); // for expand_dropdowns

      // Get user's entity
      $eid = getItemByTypeName('Entity', '_test_root_entity', true);
      $data = $this->query('getItem',
                           ['itemtype' => 'Entity',
                            'id'       => $eid,
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => ['get_hateoas' => false]]);

      $this->variable($data)->isNotFalse();
      $this->array($data)
         ->hasKey('id')
         ->hasKey('name')
         ->hasKey('completename')
         ->notHasKey('links'); // get_hateoas == false

      // Get the previously created 'computer 1'
      $data = $this->query('getItem',
                           ['itemtype' => 'Computer',
                            'id'       => $computers_id,
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => ['with_networkports' => true]]);

      $this->variable($data)->isNotFalse();

      $this->array($data)
         ->hasKey('id')
         ->hasKey('name')
         ->hasKey('_networkports');

      $this->array($data['_networkports'])
         ->hasKey('NetworkPortEthernet');

      $this->array($data['_networkports']['NetworkPortEthernet'][0])->hasKey('NetworkName');

      $networkname = $data['_networkports']['NetworkPortEthernet'][0]['NetworkName'];
      $this->array($networkname)
         ->hasKey('IPAddress')
         ->hasKey('FQDN')
         ->hasKey('id')
         ->hasKey('name');

      $this->array($networkname['IPAddress'][0])
         ->hasKey('name')
         ->hasKey('IPNetwork');

      $this->string($networkname['IPAddress'][0]['name'])->isIdenticalTo('1.2.3.4');
   }

   /**
    * @tags    api
    * @covers  API::getItem
    */
   public function testGetItemWithNotes() {
      $computer = $this->createComputer();
      $computers_id = $computer->getID();

      // try to create a new note
      $this->createNote($computers_id);

      // Get the previously created 'computer 1'
      $data = $this->query('getItem',
                           ['itemtype' => 'Computer',
                            'id'       => $computers_id,
                            'headers'  => ['Session-Token'     => $this->session_token],
                            'query'    => ['with_notes' => true]]);

      $this->variable($data)->isNotFalse();

      $this->array($data)
         ->hasKey('id')
         ->hasKey('name')
         ->hasKey('_notes');

      $this->array($data['_notes'][0])
         ->hasKey('id')
         ->hasKey('itemtype')
         ->hasKey('items_id')
         ->hasKey('users_id')
         ->hasKey('content');
   }

   /**
    * @tags    api
    * @covers  API::getItem
    */
   public function testGetItems() {
      // test retrieve all users
      $data = $this->query('getItems',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => [
                               'expand_dropdowns' => true]]);

      $this->variable($data)->isNotFalse();

      $this->array($data)
         ->hasKey('headers')
         ->hasKey(0)
         ->size->isGreaterThanOrEqualTo(4);

      unset($data['headers']);

      $this->array($data[0])
         ->hasKey('id')
         ->hasKey('name')
         ->hasKey('is_active')
         ->hasKey('entities_id')
         ->notHasKey('password');
      $this->boolean(is_numeric($data[0]['entities_id']))->isFalse(); // for expand_dropdowns

      // test retrieve partial users
      $data = $this->query('getItems',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => [
                               'range' => '0-1',
                               'expand_dropdowns' => true]],
                           206);

      $this->variable($data)->isNotFalse();

      $this->array($data)
         ->hasKey('headers')
         ->hasSize(3);
      unset($data['headers']);

      $this->array($data[0])
         ->hasKey('id')
         ->hasKey('name')
         ->hasKey('is_active')
         ->hasKey('entities_id')
         ->notHasKey('password');
      $this->boolean(is_numeric($data[0]['entities_id']))->isFalse(); // for expand_dropdowns

      // test retrieve 1 user with a text filter
      $data = $this->query('getItems',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => ['searchText' => ['name' => 'gl']]]);

      $this->variable($data)->isNotFalse();

      $this->array($data)
         ->hasKey('headers')
         ->hasSize(2);
      unset($data['headers']);

      $this->array($data[0])
         ->hasKey('id')
         ->hasKey('name');

      $this->string($data[0]['name'])->isIdenticalTo('glpi');

      // Test only_id param
      $data = $this->query('getItems',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => ['only_id' => true]]);

      $this->variable($data)->isNotFalse();

      $this->array($data)
         ->hasKey('headers')
         ->size->isGreaterThanOrEqualTo(5);

      $this->array($data[0])
         ->hasKey('id')
         ->notHasKey('name')
         ->notHasKey('is_active')
         ->notHasKey('password');

      // test retrieve all config
      $data = $this->query('getItems',
                           ['itemtype' => 'Config',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => ['expand_dropdowns' => true]]);

      $this->variable($data)->isNotFalse();
      $this->array($data)->hasKey('headers');
      unset($data['headers']);
      foreach ($data as $config_row) {
         $this->string($config_row['name'])
            ->isNotEqualTo('smtp_passwd')
            ->isNotEqualTo('proxy_passwd');
      }
   }

   /**
    * try to retrieve invalid range of users
    * We expect a http code 400
    *
    * @tags    api
    * @covers  API::getItem
    */
   public function testgetItemsInvalidRange() {
      $data = $this->query('getItems',
                           ['itemtype' => 'User',
                            'headers'  => ['Session-Token' => $this->session_token],
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
    * @tags    api
    * @covers  API::getItem
    */
   public function testgetItemsForPostonly() {
      // init session for postonly
      $data = $this->query('initSession',
                           ['query' => [
                              'login'    => 'post-only',
                              'password' => 'postonly']]);

      // create a ticket for another user (glpi - super-admin)
      $ticket = new \Ticket;
      $tickets_id = $ticket->add(['name'                => 'test post-only',
                                       'content'             => 'test post-only',
                                       '_users_id_requester' => 2]);
      $this->integer((int)$tickets_id)->isGreaterThan(0);

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

      $this->variable($data)->isNotFalse();
      $this->array($data)
         ->hasKey('headers')
         ->hasSize(1);

      // delete ticket
      $ticket->delete(['id' => $tickets_id], true);
   }

   /**
    * @tags    api
    * @covers  API::updateItems
    */
   public function testUpdateItem() {
      $computer = $this->createComputer();
      $computers_id = $computer->getID();

      $data = $this->query('updateItems',
                           ['itemtype' => 'Computer',
                            'verb'     => 'PUT',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'json'     => [
                               'input' => [
                                  'id'     => $computers_id,
                                  'serial' => "abcdef"]]]);

      $this->variable($data)->isNotFalse();

      $computer = array_shift($data);
      $this->array($computer)
         ->hasKey($computers_id)
         ->hasKey('message');
      $this->boolean((bool)$computer[$computers_id])->isTrue();

      $computer = new Computer;
      $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();
      $this->string($computer->fields['serial'])->isIdenticalTo('abcdef');
   }

   /**
    * @tags    api
    * @covers  API::updateItems
    */
   public function testUpdateItems() {
      $computers_id_collection = $this->testCreateItems();
      $input    = [];
      $computer = new Computer;
      foreach ($computers_id_collection as $key => $computers_id) {
         $input[] = ['id'          => $computers_id['id'],
                     'otherserial' => "abcdef"];
      }
      $data = $this->query('updateItems',
                           ['itemtype' => 'Computer',
                            'verb'     => 'PUT',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'json'     => ['input' => $input]]);

      $this->variable($data)->isNotFalse();
      $this->array($data)->hasKey('headers');
      unset($data['headers']);
      foreach ($data as $index => $row) {
         $computers_id = $computers_id_collection[$index]['id'];
         $this->array($row)
            ->hasKey($computers_id)
            ->hasKey('message');
         $this->boolean(true, (bool) $row[$computers_id])->isTrue();

         $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();
         $this->string($computer->fields['otherserial'])->isIdenticalTo('abcdef');
      }
   }


   /**
    * @tags    api
    * @covers  API::deleteItems
    */
   public function testDeleteItem() {
      $computer = new \Computer();
      $this->integer(
         $computer->add([
            'name'         => 'A computer to delete',
            'entities_id'  => 1
         ])
      )->isGreaterThan(0);
      $computers_id = $computer->getID();

      $data = $this->query('deleteItems',
                           ['itemtype' => 'Computer',
                            'id'       => $computers_id,
                            'verb'     => 'DELETE',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => ['force_purge' => "true"]]);

      $this->variable($data)->isNotFalse();

      $this->array($data)->hasKey('headers');
      unset($data['headers']);
      $computer = array_shift($data);
      $this->array($computer)
         ->hasKey($computers_id)
         ->hasKey('message');

      $computer = new \Computer;
      $this->boolean((bool)$computer->getFromDB($computers_id))->isFalse();
   }


   /**
    * @tags    api
    * @covers  API::deleteItems
    */
   public function testDeleteItems() {
      $computers_id_collection = $this->testCreateItems();
      $input    = [];
      $computer = new Computer;
      $lastComputer = array_pop($computers_id_collection);
      foreach ($computers_id_collection as $key => $computers_id) {
         $input[] = ['id' => $computers_id['id']];
      }
      $data = $this->query('deleteItems',
                           ['itemtype' => 'Computer',
                            'verb'     => 'DELETE',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'json'     => [
                               'input'       => $input,
                               'force_purge' => true]]);

      $this->variable($data)->isNotFalse();
      unset($data['headers']);

      foreach ($data as $index => $row) {
         $computers_id = $computers_id_collection[$index]['id'];
         $this->array($row)
            ->hasKey($computers_id)
            ->hasKey('message');
         $this->boolean((bool)$row[$computers_id])->isTrue();

         $this->boolean((bool)$computer->getFromDB($computers_id))->isFalse();
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
                            'headers'  => ['Session-Token' => $this->session_token],
                            'json'     => [
                                'input'       => $input,
                                'force_purge' => true]],
                           207);

      $this->variable($data)->isNotFalse();
      $this->boolean($data[1][0][$computers_id_collection[0]['id']])->isTrue();
      $this->array($data[1][0])->hasKey('message');
      $this->boolean($data[1][1][$computers_id_collection[1]['id']])->isFalse();
      $this->array($data[1][1])->hasKey('message');
   }

   /**
    * @tags    api
    */
   public function testInjection() {
      $data = $this->query('createItems',
                           ['itemtype' => 'Computer',
                            'verb'     => 'POST',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'json'    => [
                               'input' => [
                                  'name'        => "my computer', (SELECT `password` from `glpi_users` as `otherserial` WHERE `id`=2), '0 ' , '2016-10-26 00:00:00', '2016-10-26 00 :00 :00')#",
                                  'otherserial' => "Not hacked"]]],
                           201);

      $this->array($data)
         ->hasKey('id');
      $new_id = $data['id'];

      $computer = new Computer();
      $this->boolean((bool)$computer->getFromDB($new_id))->isTrue();

      //Add SQL injection spotted!
      $this->boolean($computer->fields['otherserial'] != 'Not hacked')->isFalse();

      $data = $this->query('updateItems',
                           ['itemtype' => 'Computer',
                            'verb'     => 'PUT',
                            'headers'  => ['Session-Token' => $this->session_token],
                             'json'    => [
                                'input' => [
                                   'id'     => $new_id,
                                   'serial' => "abcdef', `otherserial`='injected"]]]);

      $this->boolean((bool)$computer->getFromDB($new_id))->isTrue();
      //Update SQL injection spotted!
      $this->boolean($computer->fields['otherserial'] === 'injected')->isFalse();

      $computer = new Computer();
      $computer->delete(['id' => $new_id], true);
   }

   /**
    * @tags    api
    */
   public function testProtectedConfigSettings() {
      $sensitiveSettings = [
            'proxy_passwd',
            'smtp_passwd',
      ];

      // set a non empty value to the sessionts to check
      foreach ($sensitiveSettings as $name) {
         Config::setConfigurationValues('core', [$name => 'not_empty_password']);
         $value = Config::getConfigurationValues('core', [$name]);
         $this->array($value)->hasKey($name);
         $this->string($value[$name])->isNotEmpty();
      }

      $where = "'" . implode("', '", $sensitiveSettings) . "'";
      $config = new config();
      $rows = $config->find("`context`='core' AND `name` IN ($where)");
      $this->array($rows)
         ->hasSize(count($sensitiveSettings));

      // Check the value is not retrieved for sensitive settings
      foreach ($rows as $row) {
         $data = $this->query('getItem',
                              ['itemtype' => 'Config',
                               'id'       => $row['id'],
                               'headers' => ['Session-Token' => $this->session_token]]);
         $this->array($data)->notHasKey('value');
      }

      // Check an other setting is disclosed (when not empty)
      $config = new Config();
      $config->getFromDBByCrit(['context' => 'core', 'name' => 'admin_email']);
      $data = $this->query('getItem',
                           ['itemtype' => 'Config',
                            'id'       => $config->getID(),
                            'headers' => ['Session-Token' => $this->session_token]]);

      $this->variable($data['value'])->isNotEqualTo('');

      // Check a search does not disclose sensitive values
      $criteria = [];
      $queryString = "";
      foreach ($rows as $row) {
         $queryString = "&criteria[0][link]=or&criteria[0][field]=1&criteria[0][searchtype]=equals&criteria[0][value]=".$row['name'];
      }

      $data = $this->query('search',
                           ['itemtype' => 'Config',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => []],
                           206);
      foreach ($data['data'] as $row) {
         foreach ($row as $col) {
            $this->variable($col)->isNotEqualTo('not_empty_password');
         }
      }
   }

   /**
    * @tags    api
    */
   public function testProtectedDeviceSimcardFields() {
      global $DB;

      $sensitiveFields = [
            'pin',
            'pin2',
            'puk',
            'puk2',
      ];

      $obj = new \Item_DeviceSimcard();

      // Add
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $this->object($computer)->isInstanceOf('\Computer');
      $deviceSimcard = getItemByTypeName('DeviceSimcard', '_test_simcard_1');
      $this->integer((int) $deviceSimcard->getID())->isGreaterThan(0);
      $this->object($deviceSimcard)->isInstanceOf('\Devicesimcard');
      $input = [
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'devicesimcards_id'  => $deviceSimcard->getID(),
            'entities_id'        => 0,
            'pin'                => '1234',
            'pin2'               => '2345',
            'puk'                => '3456',
            'puk2'               => '4567',
      ];
      $id = $obj->add($input);
      $this->integer($id)->isGreaterThan(0);

      //drop update access on item_devicesimcard
      $DB->update(
         'glpi_profilerights',
         ['rights' => 2], [
            'profiles_id'  => 4,
            'name'         => 'devicesimcard_pinpuk'
         ]
      );

      // Profile changed then login
      $backupSessionToken = $this->session_token;
      $this->initSessionCredentials();
      $limitedSessionToken = $this->session_token;

      //reset rights. Done here so ACLs are reset even if tests fails.
      $DB->update(
         'glpi_profilerights',
         ['rights' => 3], [
            'profiles_id'  => 4,
            'name'         => 'devicesimcard_pinpuk'
         ]
      );
      $this->session_token = $backupSessionToken;

      // test getItem does not disclose sensitive fields when READ disabled
      $data = $this->query('getItem',
                           ['itemtype' => 'Item_DeviceSimcard',
                            'id'       => $id,
                            'headers'  => ['Session-Token' => $limitedSessionToken]]);
      foreach ($sensitiveFields as $field) {
         $this->array($data)->notHasKey($field);
      }

      // test getItem discloses sensitive fields when READ enabled
      $data = $this->query('getItem',
                           ['itemtype' => 'Item_DeviceSimcard',
                            'id'       => $id,
                            'headers'  => ['Session-Token' => $this->session_token]]);
      foreach ($sensitiveFields as $field) {
         $this->array($data)->hasKey($field);
      }

      // test searching a sensitive field as criteria id forbidden
      $data = $this->query('search',
                           ['itemtype' => 'Item_DeviceSimcard',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => ['criteria' => [
                                                          0 => ['field'      => 15,
                                                                'searchtype' => 'equals',
                                                                'value'      => $input['pin']
                                                               ]
                                                         ]
                                          ]
                           ],
                           400,
                           'ERROR');

      // test forcing display of a sensitive field
      $data = $this->query('search',
                           ['itemtype' => 'Item_DeviceSimcard',
                            'headers'  => ['Session-Token' => $this->session_token],
                            'query'    => [
                                           'forcedisplay'  => [15]
                            ]
                           ],
                           400,
                           'ERROR');

   }

   /**
    * @tags    api
    * @covers  API::getGlpiConfig
    */
   public function testGetGlpiConfig() {
      $data = $this->query('getGlpiConfig',
                           ['headers'  => ['Session-Token' => $this->session_token]]);

      // Test a disclosed data
      $this->array($data)
         ->hasKey('cfg_glpi');
      $this->array($data['cfg_glpi'])
         ->hasKey('infocom_types');

      // Test undisclosed data are actually not disclosed
      $this->array(Config::$undisclosedFields)
         ->size->isGreaterThan(0);
      foreach (Config::$undisclosedFields as $key) {
         $this->array($data['cfg_glpi'])->notHasKey($key);
      }
   }


   /**
    * @tags    api
    * @covers  API::killSession
    */
   public function testKillSession() {
      // test retrieve all users
      $res = $this->query('killSession',
                          ['headers' => ['Session-Token' => $this->session_token]]);
      $res = $this->query('getFullSession',
                          ['headers' => ['Session-Token' => $this->session_token]],
                          401,
                          'ERROR_SESSION_TOKEN_INVALID');
   }

   /**
    * @tags api
    * @engine inline
    */
   public function testLostPasswordRequest() {
      global $CFG_GLPI;

      $user = getItemByTypeName('User', TU_USER);
      $email = $user->getDefaultEmail();

      // Test the verb POST is not alloxed
      $res = $this->query('lostPassword',
                          ['verb'    => 'POST',
                          ],
                          400,
                          'ERROR');

      // Test the verb GET is not alloxed
      $res = $this->query('lostPassword',
                          ['verb'    => 'GET',
                          ],
                          400,
                          'ERROR');

      // Test the verb DELETE is not allowed
      $res = $this->query('lostPassword',
                          ['verb'    => 'DELETE',
                          ],
                          400,
                          'ERROR');

      $this->array($CFG_GLPI)
         ->variable['use_notifications']->isEqualTo(0)
         ->variable['notifications_mailing']->isEqualTo(0);

      // Test disabled notifications make this fail
      $res = $this->query('lostPassword',
                          ['verb'    => 'PUT',
                           'json'    => [
                            'email'  => $email
                           ]
                          ],
                          400,
                          'ERROR');

      //enable notifications
      Config::setConfigurationValues('core', [
         'use_notifications' => '1',
         'notifications_mailing' => '1'
      ]);

      // Test an unknown email is rejected
      $res = $this->query('lostPassword',
                          ['verb'    => 'PUT',
                           'json'    => [
                            'email'  => 'nonexistent@localhost.local'
                           ]
                          ],
                          400,
                          'ERROR');

      // Test a valid email is accepted
      $res = $this->query('lostPassword',
                          ['verb'    => 'PATCH',
                           'json'    => [
                            'email'  => $email
                           ]
                          ],
                          200);

      // get the password recovery token
      $user = getItemByTypeName('User', TU_USER);
      $token = $user->getField('password_forget_token');

      // Test reset password with a bad token
      $res = $this->query('lostPassword',
                          ['verb'    => 'PUT',
                           'json'    => [
                            'email'                 => $email,
                            'password_forget_token' => $token . 'bad',
                            'password'              => 'NewPassword',
                           ]
                          ],
                          400,
                          'ERROR');

      // Test reset password with the good token
      $res = $this->query('lostPassword',
                        ['verb'    => 'PATCH',
                         'json'    => [
                          'email'                 => $email,
                          'password_forget_token' => $token,
                          'password'              => 'NewPassword',
                         ]
                        ],
                        200);

      // Refresh the in-memory instance of user and get the password
      $user->getFromDB($user->getID());
      $newHash = $user->getField('password');

      // Restore the initial password in the DB
      $updateSuccess = $user->update([
            'id'        => $user->getID(),
            'password'  => TU_PASS,
            'password2' => TU_PASS
      ]);
      $this->variable($updateSuccess)->isNotFalse('password update failed');

      // Test the new password was saved
      $this->variable(\Auth::checkPassword('NewPassword', $newHash))->isNotFalse();

      //diable notifications
      Config::setConfigurationValues('core', [
         'use_notifications' => '0',
         'notifications_mailing' => '0'
      ]);
   }

   /**
    * Check consistency of Content-Range header
    *
    * @param array $data    Data
    * @param array $headers Headers
    *
    * @return void
    */
   protected function checkContentRange($data, $headers) {
      $this->integer($data['count'])->isLessThanOrEqualTo($data['totalcount']);
      $this->array($headers)->hasKey('Content-Range');
      $expectedContentRange = '0-'.($data['count'] - 1).'/'.$data['totalcount'];
      $this->string($headers['Content-Range'][0])->isIdenticalTo($expectedContentRange);
   }

   /**
    * Check consistency of empty Content-Range header
    *
    * @param array $data    Data
    * @param array $headers Headers
    *
    * @return void
    */
   protected function checkEmptyContentRange($data, $headers) {
      $this->integer($data['count'])->isLessThanOrEqualTo($data['totalcount']);
      $this->integer($data['totalcount'])->isEqualTo(0);
      $this->array($headers)->notHasKey('Content-Range');
   }
}
