<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

abstract class APIBaseClass extends TestCase
{
    protected $session_token;
    protected $http_client;
    protected $base_uri = "";
    protected $last_error;

    abstract protected function query(
        $resource = "",
        $params = [],
        $expected_codes = 200
    );

    public function setUp(): void
    {
        global $GLPI_CACHE;
        $GLPI_CACHE->clear();

        $this->initSessionCredentials();
    }

    abstract public function initSessionCredentials();

    public static function setUpBeforeClass(): void
    {
        // To bypass various right checks
        // This is mandatory to create/update/delete some items during tests.
        $_SESSION['glpishowallentities'] = 1;
        $_SESSION['glpiactive_entity']   = 0;
        $_SESSION['glpiactiveentities']  = [0];
        $_SESSION['glpiactiveentities_string'] = "'0'";

        // Force "cron" mode to prevent user related behaviors
        $_SESSION['glpicronuserrunning'] = "cron_phpunit";

        // enable api config
        $config = new Config();
        $config->update([
            'id'                              => 1,
            'enable_api'                      => true,
            'enable_api_login_credentials'    => true,
            'enable_api_login_external_token' => true
        ]);
    }

    /**
     * @tags   api
     * @covers API::initSession
     */
    public function testInitSessionUserToken()
    {
        // retrieve personal token of TU_USER user
        $user = new User();
        $uid = getItemByTypeName('User', TU_USER, true);
        $user->getFromDB($uid);
        $token = isset($user->fields['api_token']) ? $user->fields['api_token'] : "";
        if (empty($token)) {
            $token = $user->getAuthToken('api_token');
        }

        $data = $this->query(
            'initSession',
            ['query' => ['user_token' => $token]]
        );
        $this->assertNotFalse($data);
        $this->assertArrayHasKey('session_token', $data);
    }

    /**
     * @tags   api
     * @covers API::initSession
     */
    public function testAppToken()
    {
        $apiclient = new APIClient();
        $this->assertGreaterThan(
            0,
            (int)$apiclient->add([
                'name'             => 'test app token',
                'is_active'        => 1,
                'ipv4_range_start' => '127.0.0.1',
                'ipv4_range_end'   => '127.0.0.1',
                '_reset_app_token' => true,
            ])
        );

        $app_token = $apiclient->fields['app_token'];
        $this->assertNotEmpty($app_token);
        $this->assertSame(40, strlen($app_token));

        // test valid app token -> expect ok session
        $data = $this->query(
            'initSession',
            [
                'query' => [
                    'login'     => TU_USER,
                    'password'  => TU_PASS,
                    'app_token' => $app_token
                ]
            ]
        );
        $this->assertNotFalse($data);
        $this->assertArrayHasKey('session_token', $data);

        // test invalid app token -> expect error 400 and a specific code
        $this->query(
            'initSession',
            ['query' => [
                'login'     => TU_USER,
                'password'  => TU_PASS,
                'app_token' => "test_invalid_token"
            ]
            ],
            400,
            'ERROR_WRONG_APP_TOKEN_PARAMETER'
        );
    }

    /**
     * @tags    api
     * @covers  API::changeActiveEntities
     */
    public function testChangeActiveEntities()
    {
        $this->query(
            'changeActiveEntities',
            ['verb'    => 'POST',
                'headers' => ['Session-Token' => $this->session_token],
                'json'    => [
                    'entities_id'   => 'all',
                    'is_recursive'  => true
                ]
            ],
            200
        );
    }

    /**
     * @tags    api
     * @covers  API::getMyEntities
     */
    public function testGetMyEntities()
    {
        $data = $this->query(
            'getMyEntities',
            ['headers' => ['Session-Token' => $this->session_token]]
        );

        $this->assertNotFalse($data);
        $this->assertArrayHasKey('myentities', $data);
        $this->assertIsArray($data['myentities'][0]); // check presence of first entity
        $this->assertEquals(0, $data['myentities'][0]['id']); // check presence of root entity
    }

    /**
     * @tags    api
     * @covers  API::getActiveEntities
     */
    public function testGetActiveEntities()
    {
        $data = $this->query(
            'getActiveEntities',
            ['headers' => ['Session-Token' => $this->session_token]]
        );

        $this->assertNotFalse($data);
        $this->assertIsArray($data['active_entity']);

        $this->assertArrayHasKey('id', $data['active_entity']);
        $this->assertArrayHasKey('active_entity_recursive', $data['active_entity']);
        $this->assertArrayHasKey('active_entities', $data['active_entity']);
        $this->assertIsArray($data['active_entity']['active_entities']);
    }

    /**
     * @tags    api
     * @covers  API::changeActiveProfile
     */
    public function testChangeActiveProfile()
    {
        // test change to an existing and available profile
        $this->query(
            'changeActiveProfile',
            ['verb'    => 'POST',
                'headers' => ['Session-Token' => $this->session_token],
                'json'    => ['profiles_id'   => 4]
            ]
        );

        // test change to a non-existing profile
        $this->query(
            'changeActiveProfile',
            ['verb'    => 'POST',
                'headers' => ['Session-Token' => $this->session_token],
                'json'    => ['profiles_id'   => 9999]
            ],
            404,
            'ERROR_ITEM_NOT_FOUND'
        );

        // test a bad request
        $this->query(
            'changeActiveProfile',
            ['verb'    => 'POST',
                'headers' => ['Session-Token' => $this->session_token],
                'json'    => ['something_bad' => 4]
            ],
            400,
            'ERROR'
        );
    }

    /**
     * @tags    api
     * @covers  API::getMyProfiles
     */
    public function testGetMyProfiles()
    {
        $data = $this->query(
            'getMyProfiles',
            ['headers' => ['Session-Token' => $this->session_token]]
        );

        $this->assertNotFalse($data);
        $this->assertArrayHasKey('myprofiles', $data); // check presence of root key
        $this->assertArrayHasKey('id', $data['myprofiles'][0]); // check presence of id key in first profile
    }

    /**
     * @tags    api
     * @covers  API::getActiveProfile
     */
    public function testGetActiveProfile()
    {
        $data = $this->query(
            'getActiveProfile',
            ['headers' => ['Session-Token' => $this->session_token]]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('active_profile', $data);
        $this->assertIsArray($data['active_profile']);
        $this->assertArrayHasKey('id', $data['active_profile']);
        $this->assertArrayHasKey('name', $data['active_profile']);
        $this->assertArrayHasKey('interface', $data['active_profile']);
    }

    /**
     * @tags    api
     * @covers  API::getFullSession
     */
    public function testGetFullSession()
    {
        $data = $this->query(
            'getFullSession',
            ['headers' => ['Session-Token' => $this->session_token]]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('session', $data);

        $this->assertIsArray($data['session']);
        $this->assertArrayHasKey('glpiID', $data['session']);
        $this->assertArrayHasKey('glpiname', $data['session']);
        $this->assertArrayHasKey('glpiroot', $data['session']);
        $this->assertArrayHasKey('glpilanguage', $data['session']);
        $this->assertArrayHasKey('glpilist_limit', $data['session']);
    }

    /**
     * @tags    api
     * @covers  API::getMultipleItems
     */
    public function testGetMultipleItems()
    {
        // Get the User TU_USER and the entity in the same query
        $uid = getItemByTypeName('User', TU_USER, true);
        $eid = getItemByTypeName('Entity', '_test_root_entity', true);
        $data = $this->query(
            'getMultipleItems',
            ['headers' => ['Session-Token' => $this->session_token],
                'query'   => [
                    'items'            => [['itemtype' => 'User',
                        'items_id' => $uid
                    ],
                        ['itemtype' => 'Entity',
                            'items_id' => $eid
                        ]
                    ],
                    'with_logs'        => true,
                    'expand_dropdowns' => true
                ]
            ]
        );

        unset($data['headers']);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        foreach ($data as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('entities_id', $item);
            $this->assertArrayHasKey('links', $item);
            $this->assertArrayHasKey('_logs', $item);
            $this->assertArrayNotHasKey('password', $item);
            $this->assertFalse(is_numeric($item['entities_id'])); // for expand_dropdowns
        }
    }

    /**
     * @tags    api
     * @covers  API::listSearchOptions
     */
    public function testListSearchOptions()
    {
        // test retrieve all users
        $data = $this->query(
            'listSearchOptions',
            ['itemtype' => 'Computer',
                'headers'  => ['Session-Token' => $this->session_token]
            ]
        );

        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(128, count($data));

        $this->assertIsArray($data[1]);
        $this->assertSame('Name', $data[1]['name']);
        $this->assertSame('glpi_computers', $data[1]['table']);
        $this->assertSame('name', $data[1]['field']);
        $this->assertIsArray($data[1]['available_searchtypes']);

        $this->assertSame(
            ['contains', 'notcontains', 'equals', 'notequals'],
            $data[1]['available_searchtypes']
        );
    }

    /**
     * @tags    api
     * @covers  API::searchItems
     */
    public function testListSearch()
    {
        // test retrieve all users
        $data = $this->query(
            'search',
            ['itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'sort'          => 19,
                    'order'         => 'DESC',
                    'range'         => '0-10',
                    'forcedisplay'  => '81',
                    'rawdata'       => true
                ]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        $this->assertArrayHasKey('totalcount', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertArrayHasKey('sort', $data);
        $this->assertArrayHasKey('order', $data);
        $this->assertArrayHasKey('rawdata', $data);

        $headers = $data['headers'];
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('Accept-Range', $headers);
        $this->assertStringStartsWith('User', $headers['Accept-Range'][0]);
        $this->assertCount(9, $data['rawdata']);

        $first_user = array_shift($data['data']);
        $second_user = array_shift($data['data']);

        $this->assertArrayHasKey(81, $first_user);
        $this->assertArrayHasKey(81, $second_user);

        $this->checkContentRange($data, $headers);
    }

    /**
     * @tags    api
     * @covers  API::searchItems
     */
    public function testListSearchPartial()
    {
        // test retrieve partial users
        $data = $this->query(
            'search',
            ['itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'sort'          => 19,
                    'order'         => 'DESC',
                    'range'         => '0-2',
                    'forcedisplay'  => '81',
                    'rawdata'       => true
                ]
            ],
            206
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('totalcount', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertArrayHasKey('sort', $data);
        $this->assertArrayHasKey('order', $data);
        $this->assertArrayHasKey('rawdata', $data);

        $this->assertCount(9, $data['rawdata']);

        $first_user = array_shift($data['data']);
        $second_user = array_shift($data['data']);
        $this->assertArrayHasKey(81, $first_user);
        $this->assertArrayHasKey(81, $second_user);

        $this->checkContentRange($data, $data['headers']);
    }

    /**
     * @tags    api
     * @covers  API::searchItems
     */
    public function testListSearchEmpty()
    {
        // test retrieve partial users
        $data = $this->query(
            'search',
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
                    ]
                ]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        $this->assertArrayHasKey('totalcount', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertArrayHasKey('sort', $data);
        $this->assertArrayHasKey('order', $data);
        $this->assertArrayHasKey('rawdata', $data);

        $this->assertArrayHasKey('Accept-Range', $data['headers']);
        $this->assertStringStartsWith('User', $data['headers']['Accept-Range'][0]);

        $this->assertCount(9, $data['rawdata']);
        $this->checkEmptyContentRange($data, $data['headers']);
    }

    /**
     * @tags    api
     * @covers  API::searchItems
     */
    public function testSearchWithBadCriteria()
    {
        // test retrieve all users
        // multidimensional array of vars in query string not supported ?

        // test a non-existing search option ID
        $this->query(
            'search',
            ['itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'reset'    => 'reset',
                    'criteria' => [[
                        'field'      => '134343',
                        'searchtype' => 'contains',
                        'value'      => 'dsadasd',
                    ]
                    ]
                ]
            ],
            400,   // 400 code expected (error, bad request)
            'ERROR'
        );

        // test a non-numeric search option ID
        $this->query(
            'search',
            ['itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'reset'    => 'reset',
                    'criteria' => [[
                        'field'      => '\134343',
                        'searchtype' => 'contains',
                        'value'      => 'dsadasd',
                    ]
                    ]
                ]
            ],
            400,   // 400 code expected (error, bad request)
            'ERROR'
        );

        // test an incomplete criteria
        $this->query(
            'search',
            ['itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'reset'    => 'reset',
                    'criteria' => [[
                        'field'      => '134343',
                        'searchtype' => 'contains',
                    ]
                    ]
                ]
            ],
            400,  // 400 code expected (error, bad request)
            'ERROR'
        );
    }

    /**
     * @tags    api
     */
    protected function badEndpoint($expected_code = null, $expected_symbol = null)
    {
        $this->query(
            'badEndpoint',
            [
                'headers' => [
                    'Session-Token' => $this->session_token
                ]
            ],
            $expected_code,
            $expected_symbol
        );
    }

    /**
     * Create a computer
     *
     * @return Computer
     */
    protected function createComputer()
    {
        $data = $this->query(
            'createItems',
            ['verb'     => 'POST',
                'itemtype' => 'Computer',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => ['input' => ['name' => "My single computer "]]
            ],
            201
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('message', $data);

        $computers_id = $data['id'];
        $this->assertTrue(is_numeric($computers_id));
        $this->assertGreaterThanOrEqual(0, (int)$computers_id);

        $computer = new Computer();
        $this->assertTrue((bool)$computer->getFromDB($computers_id));
        return $computer;
    }

    /**
     * Create a network port
     *
     * @param integer $computers_id Computer ID
     *
     * @return void
     */
    protected function createNetworkPort($computers_id)
    {
        $data = $this->query(
            'createItems',
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
                                  // add an extra key to the next array
                                  // to avoid xmlrpc losing -1 key.
                                  // see https://bugs.php.net/bug.php?id=37746
                        'NetworkName__ipaddresses' => ['-1'                => "1.2.3.4",
                            '_xmlrpc_fckng_fix' => ''
                        ],
                        '_create_children'         => true
                    ]
                ]
            ],
            201
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('message', $data);
    }

    /**
     * Create a note
     *
     * @param integer $computers_id Computer ID
     *
     * @return void
     */
    protected function createNote($computers_id)
    {
        $data = $this->query(
            'createItems',
            ['verb'     => 'POST',
                'itemtype' => 'Notepad',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => [
                    'input' => [
                        'itemtype' => 'Computer',
                        'items_id' => $computers_id,
                        'content'  => 'note about a computer'
                    ]
                ]
            ],
            201
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('message', $data);
    }

    /**
     * @tags    api
     * @covers  API::CreateItems
     */
    public function testCreateItem()
    {
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
    public function testCreateItems()
    {
        $data = $this->query(
            'createItems',
            ['verb'     => 'POST',
                'itemtype' => 'Computer',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => [
                    'input' => [[
                        'name' => "My computer 2"
                    ],[
                        'name' => "My computer 3"
                    ],[
                        'name' => "My computer 4"
                    ]
                    ]
                ]
            ],
            201
        );

        $this->assertNotFalse($data);

        $first_computer = $data[0];
        $second_computer = $data[1];

        $this->assertArrayHasKey('id', $first_computer);
        $this->assertArrayHasKey('message', $first_computer);
        $this->assertArrayHasKey('id', $second_computer);
        $this->assertArrayHasKey('message', $second_computer);

        $this->assertTrue(is_numeric($first_computer['id']));
        $this->assertTrue(is_numeric($second_computer['id']));

        $this->assertGreaterThanOrEqual(0, (int)$first_computer['id']);
        $this->assertGreaterThanOrEqual(0, (int)$second_computer['id']);

        $computer = new Computer();
        $this->assertTrue((bool)$computer->getFromDB($first_computer['id']));
        $this->assertTrue((bool)$computer->getFromDB($second_computer['id']));

        unset($data['headers']);
        return $data;
    }

    /**
     * @tags    apit
     * @covers  API::getItem
     */
    public function testGetItem()
    {
        $computer = $this->createComputer();
        $computers_id = $computer->getID();
        // Get the User TU_USER
        $uid = getItemByTypeName('User', TU_USER, true);
        $data = $this->query(
            'getItem',
            ['itemtype' => 'User',
                'id'       => $uid,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'expand_dropdowns' => true,
                    'with_logs'        => true
                ]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('entities_id', $data);
        $this->assertArrayHasKey('links', $data);
        $this->assertArrayHasKey('_logs', $data);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertFalse(is_numeric($data['entities_id'])); // for expand_dropdowns

        // Get user's entity
        $eid = getItemByTypeName('Entity', '_test_root_entity', true);
        $data = $this->query(
            'getItem',
            ['itemtype' => 'Entity',
                'id'       => $eid,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['get_hateoas' => false]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('completename', $data);
        $this->assertArrayNotHasKey('links', $data); // get_hateoas == false

        // Get the previously created 'computer 1'
        $data = $this->query(
            'getItem',
            ['itemtype' => 'Computer',
                'id'       => $computers_id,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['with_networkports' => true]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('_networkports', $data);

        $this->assertArrayHasKey('NetworkPortEthernet', $data['_networkports']);
        $this->assertEmpty($data['_networkports']['NetworkPortEthernet']);

        // create a network port for the computer
        $this->createNetworkPort($computers_id);

        $data = $this->query(
            'getItem',
            ['itemtype' => 'Computer',
                'id'       => $computers_id,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['with_networkports' => true]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('_networkports', $data);

        $this->assertArrayHasKey('NetworkPortEthernet', $data['_networkports']);
        $this->assertNotEmpty($data['_networkports']['NetworkPortEthernet']);
        $this->assertArrayHasKey('NetworkName', $data['_networkports']['NetworkPortEthernet'][0]);

        $networkname = $data['_networkports']['NetworkPortEthernet'][0]['NetworkName'];
        $this->assertIsArray($networkname);
        $this->assertArrayHasKey('IPAddress', $networkname);
        $this->assertArrayHasKey('FQDN', $networkname);
        $this->assertArrayHasKey('id', $networkname);
        $this->assertArrayHasKey('name', $networkname);

        $this->assertIsArray($networkname['IPAddress'][0]);
        $this->assertArrayHasKey('name', $networkname['IPAddress'][0]);
        $this->assertArrayHasKey('IPNetwork', $networkname['IPAddress'][0]);

        $this->assertSame('1.2.3.4', $networkname['IPAddress'][0]['name']);
    }

    /**
     * @tags    api
     * @covers  API::getItem
     */
    public function testGetItemWithNotes()
    {
        $computer = $this->createComputer();
        $computers_id = $computer->getID();

        // try to create a new note
        $this->createNote($computers_id);

        // Get the previously created 'computer 1'
        $data = $this->query(
            'getItem',
            ['itemtype' => 'Computer',
                'id'       => $computers_id,
                'headers'  => ['Session-Token'     => $this->session_token],
                'query'    => ['with_notes' => true]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('_notes', $data);

        $this->assertIsArray($data['_notes'][0]);
        $this->assertArrayHasKey('id', $data['_notes'][0]);
        $this->assertArrayHasKey('itemtype', $data['_notes'][0]);
        $this->assertArrayHasKey('items_id', $data['_notes'][0]);
        $this->assertArrayHasKey('users_id', $data['_notes'][0]);
        $this->assertArrayHasKey('content', $data['_notes'][0]);
    }

    /**
     * @tags    api
     * @covers  API::getItem
     */
    public function testGetItems()
    {
        // test retrieve all users
        $data = $this->query(
            'getItems',
            ['itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'expand_dropdowns' => true
                ]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        $this->assertArrayHasKey(0, $data);
        $this->assertGreaterThanOrEqual(4, count($data));

        unset($data['headers']);

        $this->assertIsArray($data[0]);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('is_active', $data[0]);
        $this->assertArrayHasKey('entities_id', $data[0]);
        $this->assertArrayNotHasKey('password', $data[0]);
        $this->assertFalse(is_numeric($data[0]['entities_id'])); // for expand_dropdowns

        // test retrieve partial users
        $data = $this->query(
            'getItems',
            ['itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'range' => '0-1',
                    'expand_dropdowns' => true
                ]
            ],
            206
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        $this->assertCount(3, $data);
        unset($data['headers']);

        $this->assertIsArray($data[0]);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertArrayHasKey('is_active', $data[0]);
        $this->assertArrayHasKey('entities_id', $data[0]);
        $this->assertArrayNotHasKey('password', $data[0]);
        $this->assertFalse(is_numeric($data[0]['entities_id'])); // for expand_dropdowns

        // test retrieve 1 user with a text filter
        $data = $this->query(
            'getItems',
            ['itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['searchText' => ['name' => 'gl']]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        $this->assertCount(2, $data);
        unset($data['headers']);

        $this->assertIsArray($data[0]);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('name', $data[0]);

        $this->assertSame('glpi', $data[0]['name']);

        // Test only_id param
        $data = $this->query(
            'getItems',
            ['itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['only_id' => true]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        $this->assertGreaterThanOrEqual(5, count($data));

        $this->assertIsArray($data[0]);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayNotHasKey('name', $data[0]);
        $this->assertArrayNotHasKey('is_active', $data[0]);
        $this->assertArrayNotHasKey('password', $data[0]);

        // test retrieve all config
        $data = $this->query(
            'getItems',
            ['itemtype' => 'Config',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['expand_dropdowns' => true]
            ],
            206
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
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
     * @tags    api
     * @covers  API::getItem
     */
    public function testgetItemsInvalidRange()
    {
        $this->query(
            'getItems',
            ['itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'range' => '100-105',
                    'expand_dropdowns' => true
                ]
            ],
            400,
            'ERROR_RANGE_EXCEED_TOTAL'
        );
    }

    /**
     * This function test https://github.com/glpi-project/glpi/issues/1103
     * A post-only user could retrieve tickets of others users when requesting itemtype
     * without first letter in uppercase
     *
     * @tags    api
     * @covers  API::getItem
     */
    public function testgetItemsForPostonly()
    {
        // init session for postonly
        $data = $this->query(
            'initSession',
            ['query' => [
                'login'    => 'post-only',
                'password' => 'postonly'
            ]
            ]
        );

        // create a ticket for another user (glpi - super-admin)
        $ticket = new \Ticket();
        $tickets_id = $ticket->add(['name'                => 'test post-only',
            'content'             => 'test post-only',
            '_users_id_requester' => 2
        ]);
        $this->assertGreaterThan(0, (int)$tickets_id);

        // try to access this ticket with post-only
        $this->query(
            'getItem',
            ['itemtype' => 'Ticket',
                'id'       => $tickets_id,
                'headers'  => [
                    'Session-Token' => $data['session_token']
                ]
            ],
            403,
            'ERROR_RIGHT_MISSING'
        );

        // try to access ticket list (we should get empty return)
        $data = $this->query(
            'getItems',
            ['itemtype' => 'Ticket',
                'headers'  => ['Session-Token' => $data['session_token'],
                    'query'    => [
                        'expand_dropdowns' => true
                    ]
                ]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        $this->assertCount(1, $data);

        // delete ticket
        $ticket->delete(['id' => $tickets_id], true);
    }

    /**
     * @tags    api
     * @covers  API::updateItems
     */
    public function testUpdateItem()
    {
        $computer = $this->createComputer();
        $computers_id = $computer->getID();

        $data = $this->query(
            'updateItems',
            ['itemtype' => 'Computer',
                'verb'     => 'PUT',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => [
                    'input' => [
                        'id'     => $computers_id,
                        'serial' => "abcdef"
                    ]
                ]
            ]
        );

        $this->assertIsArray($data);

        $computer = array_shift($data);
        $this->assertIsArray($computer);
        $this->assertArrayHasKey($computers_id, $computer);
        $this->assertArrayHasKey('message', $computer);
        $this->assertTrue((bool)$computer[$computers_id]);

        $computer = new Computer();
        $this->assertTrue((bool)$computer->getFromDB($computers_id));
        $this->assertSame('abcdef', $computer->fields['serial']);
    }

    /**
     * @tags    api
     * @covers  API::updateItems
     */
    public function testUpdateItems()
    {
        $computers_id_collection = $this->testCreateItems();
        $input    = [];
        $computer = new Computer();
        foreach ($computers_id_collection as $key => $computers_id) {
            $input[] = ['id'          => $computers_id['id'],
                'otherserial' => "abcdef"
            ];
        }
        $data = $this->query(
            'updateItems',
            ['itemtype' => 'Computer',
                'verb'     => 'PUT',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => ['input' => $input]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        unset($data['headers']);
        foreach ($data as $index => $row) {
            $computers_id = $computers_id_collection[$index]['id'];
            $this->assertIsArray($row);
            $this->assertArrayHasKey($computers_id, $row);
            $this->assertArrayHasKey('message', $row);
            $this->assertTrue(true, (bool) $row[$computers_id]);

            $this->assertTrue((bool)$computer->getFromDB($computers_id));
            $this->assertSame('abcdef', $computer->fields['otherserial']);
        }
    }


    /**
     * @tags    api
     * @covers  API::deleteItems
     */
    public function testDeleteItem()
    {
        $eid = getItemByTypeName('Entity', '_test_root_entity', true);
        $_SESSION['glpiactive_entity'] = $eid;
        $computer = new \Computer();
        $this->assertGreaterThan(
            0,
            $computer->add([
                'name'         => 'A computer to delete',
                'entities_id'  => $eid
            ])
        );
        $computers_id = $computer->getID();

        $data = $this->query(
            'deleteItems',
            ['itemtype' => 'Computer',
                'id'       => $computers_id,
                'verb'     => 'DELETE',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['force_purge' => "true"]
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        unset($data['headers']);
        $computer = array_shift($data);
        $this->assertIsArray($computer);
        $this->assertArrayHasKey($computers_id, $computer);
        $this->assertArrayHasKey('message', $computer);

        $computer = new \Computer();
        $this->assertFalse((bool)$computer->getFromDB($computers_id));
    }


    /**
     * @tags    api
     * @covers  API::deleteItems
     */
    public function testDeleteItems()
    {
        $computers_id_collection = $this->testCreateItems();
        $input    = [];
        $computer = new Computer();
        $lastComputer = array_pop($computers_id_collection);
        foreach ($computers_id_collection as $key => $computers_id) {
            $input[] = ['id' => $computers_id['id']];
        }
        $data = $this->query(
            'deleteItems',
            ['itemtype' => 'Computer',
                'verb'     => 'DELETE',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => [
                    'input'       => $input,
                    'force_purge' => true
                ]
            ]
        );

        $this->assertIsArray($data);
        unset($data['headers']);

        foreach ($data as $index => $row) {
            $computers_id = $computers_id_collection[$index]['id'];
            $this->assertIsArray($row);
            $this->assertArrayHasKey($computers_id, $row);
            $this->assertArrayHasKey('message', $row);
            $this->assertTrue((bool)$row[$computers_id]);

            $this->assertFalse((bool)$computer->getFromDB($computers_id));
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
        $data = $this->query(
            'deleteItems',
            ['itemtype' => 'Computer',
                'verb'     => 'DELETE',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => [
                    'input'       => $input,
                    'force_purge' => true
                ]
            ],
            207
        );

        $this->assertIsArray($data);
        $this->assertTrue($data[1][0][$computers_id_collection[0]['id']]);
        $this->assertArrayHasKey('message', $data[1][0]);
        $this->assertFalse($data[1][1][$computers_id_collection[1]['id']]);
        $this->assertArrayHasKey('message', $data[1][1]);
    }

    /**
     * @tags    api
     */
    public function testInjection()
    {
        $data = $this->query(
            'createItems',
            ['itemtype' => 'Computer',
                'verb'     => 'POST',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'    => [
                    'input' => [
                        'name'        => "my computer', (SELECT `password` from `glpi_users` as `otherserial` WHERE `id`=2), '0 ' , '2016-10-26 00:00:00', '2016-10-26 00 :00 :00')#",
                        'otherserial' => "Not hacked"
                    ]
                ]
            ],
            201
        );

        $this->assertArrayHasKey('id', $data);
        $new_id = $data['id'];

        $computer = new Computer();
        $this->assertTrue((bool)$computer->getFromDB($new_id));

        //Add SQL injection spotted!
        $this->assertFalse($computer->fields['otherserial'] != 'Not hacked');

        $this->query(
            'updateItems',
            ['itemtype' => 'Computer',
                'verb'     => 'PUT',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'    => [
                    'input' => [
                        'id'     => $new_id,
                        'serial' => "abcdef', `otherserial`='injected"
                    ]
                ]
            ]
        );

        $this->assertTrue((bool)$computer->getFromDB($new_id));
        //Update SQL injection spotted!
        $this->assertFalse($computer->fields['otherserial'] === 'injected');

        $computer = new Computer();
        $computer->delete(['id' => $new_id], true);
    }

    /**
     * @tags    api
     */
    public function testProtectedConfigSettings()
    {
        $sensitiveSettings = [
            'proxy_passwd',
            'smtp_passwd',
        ];

        // set a non-empty value to the sessions to check
        foreach ($sensitiveSettings as $name) {
            Config::setConfigurationValues('core', [$name => 'not_empty_password']);
            $value = Config::getConfigurationValues('core', [$name]);
            $this->assertArrayHasKey($name, $value);
            $this->assertNotEmpty($value[$name]);
        }

        $config = new Config();
        $rows = $config->find(['context' => 'core', 'name' => $sensitiveSettings]);
        $this->assertCount(count($sensitiveSettings), $rows);

        // Check the value is not retrieved for sensitive settings
        foreach ($rows as $row) {
            $data = $this->query(
                'getItem',
                ['itemtype' => 'Config',
                    'id'       => $row['id'],
                    'headers' => ['Session-Token' => $this->session_token]
                ]
            );
            $this->assertArrayNotHasKey('value', $data);
        }

        // Check another setting is disclosed (when not empty)
        $config = new Config();
        $config->getFromDBByCrit(['context' => 'core', 'name' => 'admin_email']);
        $data = $this->query(
            'getItem',
            ['itemtype' => 'Config',
                'id'       => $config->getID(),
                'headers' => ['Session-Token' => $this->session_token]
            ]
        );

        $this->assertNotEquals('', $data['value']);

        // Check a search does not disclose sensitive values
        $criteria = [];
        $queryString = "";
        foreach ($rows as $row) {
            $queryString = "&criteria[0][link]=or&criteria[0][field]=1&criteria[0][searchtype]=equals&criteria[0][value]=" . $row['name'];
        }

        $data = $this->query(
            'search',
            ['itemtype' => 'Config',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => []
            ],
            206
        );
        foreach ($data['data'] as $row) {
            foreach ($row as $col) {
                $this->assertNotEquals('not_empty_password', $col);
            }
        }
    }

    /**
     * @tags    api
     */
    public function testProtectedDeviceSimcardFields()
    {
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
        $this->assertInstanceOf(\Computer::class, $computer);
        $deviceSimcard = getItemByTypeName('DeviceSimcard', '_test_simcard_1');
        $this->assertGreaterThan(0, (int) $deviceSimcard->getID());
        $this->assertInstanceOf(\DeviceSimcard::class, $deviceSimcard);
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
        $this->assertGreaterThan(0, $id);

        //drop update access on item_devicesimcard
        $DB->update(
            'glpi_profilerights',
            ['rights' => 2],
            [
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
            ['rights' => 3],
            [
                'profiles_id'  => 4,
                'name'         => 'devicesimcard_pinpuk'
            ]
        );
        $this->session_token = $backupSessionToken;

        // test getItem does not disclose sensitive fields when READ disabled
        $data = $this->query(
            'getItem',
            ['itemtype' => 'Item_DeviceSimcard',
                'id'       => $id,
                'headers'  => ['Session-Token' => $limitedSessionToken]
            ]
        );
        foreach ($sensitiveFields as $field) {
            $this->assertArrayNotHasKey($field, $data);
        }

        // test getItem discloses sensitive fields when READ enabled
        $data = $this->query(
            'getItem',
            ['itemtype' => 'Item_DeviceSimcard',
                'id'       => $id,
                'headers'  => ['Session-Token' => $this->session_token]
            ]
        );
        foreach ($sensitiveFields as $field) {
            $this->assertArrayHasKey($field, $data);
        }

        // test searching a sensitive field as criteria id forbidden
        $this->query(
            'search',
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
            'ERROR'
        );

        // test forcing display of a sensitive field
        $this->query(
            'search',
            ['itemtype' => 'Item_DeviceSimcard',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'forcedisplay'  => [15]
                ]
            ],
            400,
            'ERROR'
        );
    }

    /**
     * @tags    api
     * @covers  API::getGlpiConfig
     */
    public function testGetGlpiConfig()
    {
        $data = $this->query(
            'getGlpiConfig',
            ['headers'  => ['Session-Token' => $this->session_token]]
        );

        // Test a disclosed data
        $this->assertArrayHasKey('cfg_glpi', $data);
        $this->assertArrayHasKey('infocom_types', $data['cfg_glpi']);
    }


    public function testUndisclosedField()
    {
       // test common cases
        $itemtypes = [
            'APIClient', 'AuthLDAP', 'MailCollector', 'User'
        ];
        foreach ($itemtypes as $itemtype) {
            $data = $this->query(
                'getItems',
                [
                    'itemtype' => $itemtype,
                    'headers'  => ['Session-Token' => $this->session_token]
                ]
            );

            $this->assertGreaterThan(0, count($itemtype::$undisclosedFields));

            foreach ($itemtype::$undisclosedFields as $key) {
                $this->assertIsArray($data);
                unset($data['headers']);
                foreach ($data as $item) {
                    $this->assertArrayNotHasKey($key, $item);
                }
            }
        }

        // test specific cases
        // Config
        $data = $this->query('getGlpiConfig', [
            'headers'  => ['Session-Token' => $this->session_token]
        ]);

        // Test undisclosed data are actually not disclosed
        $this->assertGreaterThan(0, count(Config::$undisclosedFields));
        foreach (Config::$undisclosedFields as $key) {
            $this->assertArrayNotHasKey($key, $data['cfg_glpi']);
        }
    }


    /**
     * @tags    api
     * @covers  API::killSession
     */
    public function testKillSession()
    {
        // test retrieve all users
        $this->query(
            'killSession',
            ['headers' => ['Session-Token' => $this->session_token]]
        );
        $this->query(
            'getFullSession',
            ['headers' => ['Session-Token' => $this->session_token]],
            401,
            'ERROR_SESSION_TOKEN_INVALID'
        );
    }

    /**
     * @tags api
     * @engine inline
     */
    public function testLostPasswordRequest()
    {
        $user = getItemByTypeName('User', TU_USER);
        $email = $user->getDefaultEmail();

        // Check that the POST method is not allowed
        $this->query(
            'lostPassword',
            ['verb'    => 'POST',
            ],
            400,
            'ERROR'
        );

        // Check that the GET method is not allowed
        $this->query(
            'lostPassword',
            ['verb'    => 'GET',
            ],
            400,
            'ERROR'
        );

        // Check that the DELETE method is not allowed
        $this->query(
            'lostPassword',
            ['verb'    => 'DELETE',
            ],
            400,
            'ERROR'
        );

        // Disable notifications
        Config::setConfigurationValues('core', [
            'use_notifications' => '0',
            'notifications_mailing' => '0'
        ]);

        // Check that disabled notifications prevent password changes
        $this->query(
            'lostPassword',
            ['verb'    => 'PUT',
                'json'    => [
                    'email'  => $email
                ]
            ],
            400,
            'ERROR'
        );

        // Enable notifications
        Config::setConfigurationValues('core', [
            'use_notifications' => '1',
            'notifications_mailing' => '1'
        ]);

        // Test an unknown email, query will succeed to avoid exposing whether
        // the email actually exist in our database but there will be a
        // warning in the server logs
        $this->query('lostPassword', [
            'verb'    => 'PUT',
            'json'    => [
                'email'  => 'nonexistent@localhost.local'
            ],
            'server_errors' => [
                "Failed to find a single user for 'nonexistent@localhost.local', 0 user(s) found."
            ]
        ], 200);

        // Test a valid email is accepted
        $this->query(
            'lostPassword',
            ['verb'    => 'PATCH',
                'json'    => [
                    'email'  => $email
                ]
            ],
            200
        );

        // get the password recovery token
        $user = getItemByTypeName('User', TU_USER);
        $token = $user->fields['password_forget_token'];
        $this->assertNotEmpty($token);

        // Test reset password with a bad token
        $this->query(
            'lostPassword',
            ['verb'    => 'PUT',
                'json'    => [
                    'email'                 => $email,
                    'password_forget_token' => $token . 'bad',
                    'password'              => 'NewPassword',
                ]
            ],
            400,
            'ERROR'
        );

        // Test reset password with the good token
        $this->query(
            'lostPassword',
            ['verb'    => 'PATCH',
                'json'    => [
                    'email'                 => $email,
                    'password_forget_token' => $token,
                    'password'              => 'NewPassword',
                ]
            ],
            200
        );

        // Refresh the in-memory instance of user and get the password
        $user->getFromDB($user->getID());
        $newHash = $user->getField('password');

        // Restore the initial password in the DB
        global $DB;
        $updateSuccess = $DB->update(
            'glpi_users',
            ['password' => Auth::getPasswordHash(TU_PASS)],
            ['id'       => $user->getID()]
        );
        $this->assertNotFalse($updateSuccess, 'password update failed');

        // Test the new password was saved
        $this->assertNotFalse(\Auth::checkPassword('NewPassword', $newHash));

        // Validates that password reset token has been removed
        $user = getItemByTypeName('User', TU_USER);
        $token = $user->fields['password_forget_token'];
        $this->assertEmpty($token);

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
    protected function checkContentRange($data, $headers)
    {
        $this->assertLessThanOrEqual($data['totalcount'], $data['count']);
        $this->assertArrayHasKey('Content-Range', $headers);
        $expectedContentRange = '0-' . ($data['count'] - 1) . '/' . $data['totalcount'];
        $this->assertSame($expectedContentRange, $headers['Content-Range'][0]);
    }

    /**
     * Check consistency of empty Content-Range header
     *
     * @param array $data    Data
     * @param array $headers Headers
     *
     * @return void
     */
    protected function checkEmptyContentRange($data, $headers)
    {
        $this->assertLessThanOrEqual($data['totalcount'], $data['count']);
        $this->assertEquals(0, $data['totalcount']);
        $this->assertArrayNotHasKey('Content-Range', $headers);
    }

    public function testUndisclosedNotificationContent()
    {
        // Enable notifications
        Config::setConfigurationValues('core', [
            'use_notifications' => '1',
            'notifications_mailing' => '1'
        ]);

        // Trigger a notification sending
        $user = getItemByTypeName('User', TU_USER);
        $this->query(
            'lostPassword',
            [
                'verb'    => 'PATCH',
                'json'    => [
                    'email'  => $user->getDefaultEmail()
                ]
            ],
            200
        );

        // need to be GLPI to access the root entity notifications
        $data = $this->query(
            'initSession',
            [
                'query' => [
                    'login'    => 'glpi',
                    'password' => 'glpi'
                ]
            ]
        );
        $this->assertArrayHasKey('session_token', $data);

        // Check notifications returned by `getItems`
        $result = $this->query(
            'getItems',
            [
                'itemtype' => QueuedNotification::class,
                'headers'  => ['Session-Token' => $data['session_token']],
            ],
            200
        );
        $this->assertIsArray($result);
        unset($result['headers']);

        $notifications = \array_filter(
            $result,
            fn ($notification) => $notification['name'] === '[GLPI] Forgotten password?'
        );

        $this->assertNotEmpty($notifications);

        foreach ($notifications as $notification) {
            $this->assertEquals('********', $notification['body_html']);
            $this->assertEquals('********', $notification['body_text']);
        }

        // Check notifications returned by a search request
        $result = $this->query(
            'search',
            [
                'itemtype' => QueuedNotification::class,
                'headers'  => ['Session-Token' => $data['session_token']],
                'query'    => [
                    'reset'         => 'reset',
                    'forcedisplay'  => [12, 13]
                ]
            ],
            200
        );
        $this->assertIsArray($result);
        unset($result['headers']);

        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['data']);

        $notifications = \array_filter(
            $result['data'],
            fn ($notification) => $notification['1'] === '[GLPI] Forgotten password?'
        );

        foreach ($notifications as $notification) {
            $this->assertEquals('********', $notification['12']); // 12 = body_html
            $this->assertEquals('********', $notification['13']); // 13 = body_text
        }
    }
}
