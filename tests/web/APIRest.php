<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units\Glpi\Api;

use APIClient;
use Auth;
use atoum\atoum;
use Computer;
use Config;
use Glpi\Tests\Api\Deprecated\Computer_Item;
use Glpi\Tests\Api\Deprecated\Computer_SoftwareLicense;
use Glpi\Tests\Api\Deprecated\Computer_SoftwareVersion;
use Glpi\Tests\Api\Deprecated\ComputerAntivirus;
use Glpi\Tests\Api\Deprecated\ComputerVirtualMachine;
use Glpi\Tests\Api\Deprecated\TicketFollowup;
use GuzzleHttp;
use Item_DeviceSimcard;
use Notepad;
use TicketTemplate;
use TicketTemplateMandatoryField;
use User;

/**
 * @engine isolate
 */
class APIRest extends atoum
{
    protected $session_token;
    /** @var GuzzleHttp\Client */
    protected $http_client;
    protected $base_uri = "";
    protected $last_error;

    public function beforeTestMethod($method)
    {
        global $CFG_GLPI, $GLPI_CACHE;

        $GLPI_CACHE->clear();

        // Empty log file
        $file_updated = file_put_contents($this->getLogFilePath(), "");
        $this->variable($file_updated)->isNotIdenticalTo(false);

        $this->http_client = new GuzzleHttp\Client();
        $this->base_uri    = trim($CFG_GLPI['url_base_api'], "/") . "/";

        $this->initSessionCredentials();
    }

    public function afterTestMethod($method)
    {
        // Check that no errors occurred on the test server
        $this->string(file_get_contents($this->getLogFilePath()))->isEmpty();
    }

    protected function getLogFilePath(): string
    {
        return GLPI_LOG_DIR . "/php-errors.log";
    }

    public function setUp()
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
    public function testAppToken()
    {
        $apiclient = new APIClient();
        $this->integer(
            (int)$apiclient->add([
                'name'             => 'test app token',
                'is_active'        => 1,
                'ipv4_range_start' => '127.0.0.1',
                'ipv4_range_end'   => '127.0.0.1',
                '_reset_app_token' => true,
            ])
        )->isGreaterThan(0);

        $app_token = $apiclient->fields['app_token'];
        $this->string($app_token)->isNotEmpty()->hasLength(40);

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
        $this->variable($data)->isNotFalse();
        $this->array($data)->hasKey('session_token');

        // test invalid app token -> expect error 400 and a specific code
        $data = $this->query(
            'initSession',
            [
                'query' => [
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
            [
                'verb'    => 'POST',
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
    public function testGetActiveEntities()
    {
        $data = $this->query(
            'getActiveEntities',
            ['headers' => ['Session-Token' => $this->session_token]]
        );

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
    public function testChangeActiveProfile()
    {
        // test change to an existing and available profile
        $this->query(
            'changeActiveProfile',
            [
                'verb'    => 'POST',
                'headers' => ['Session-Token' => $this->session_token],
                'json'    => ['profiles_id'   => 4]
            ]
        );

        // test change to a non existing profile
        $this->query(
            'changeActiveProfile',
            [
                'verb'    => 'POST',
                'headers' => ['Session-Token' => $this->session_token],
                'json'    => ['profiles_id'   => 9999]
            ],
            404,
            'ERROR_ITEM_NOT_FOUND'
        );

        // test a bad request
        $this->query(
            'changeActiveProfile',
            [
                'verb'    => 'POST',
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

        $this->variable($data)->isNotFalse();
        $this->array($data)->hasKey('myprofiles'); // check presence of root key
        $this->array($data['myprofiles'][0])->hasKey('id'); // check presence of id key in first profile
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

        $this->variable($data)->isNotFalse();
        $this->array($data)->hasKey('active_profile');
        $this->array($data['active_profile'])
            ->hasKey('id')
            ->hasKey('name')
            ->hasKey('interface');
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
    public function testGetMultipleItems()
    {
        // Get the User TU_USER and the entity in the same query
        $uid = getItemByTypeName('User', TU_USER, true);
        $eid = getItemByTypeName('Entity', '_test_root_entity', true);
        $data = $this->query(
            'getMultipleItems',
            [
                'headers' => ['Session-Token' => $this->session_token],
                'query'   => [
                    'items'            => [
                        [
                            'itemtype' => 'User',
                            'items_id' => $uid
                        ],
                        [
                            'itemtype' => 'Entity',
                            'items_id' => $eid
                        ]
                    ],
                    'with_logs'        => true,
                    'expand_dropdowns' => true
                ]
            ]
        );

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
    public function testListSearchOptions()
    {
        // test retrieve all users
        $data = $this->query(
            'listSearchOptions',
            [
                'itemtype' => 'Computer',
                'headers'  => ['Session-Token' => $this->session_token]
            ]
        );

        $this->variable($data)->isNotFalse();
        $this->array($data)
            ->size->isGreaterThanOrEqualTo(128);

        $this->array($data[1])
            ->string['name']->isIdenticalTo('Name')
            ->string['table']->isIdenticalTo('glpi_computers')
            ->string['field']->isIdenticalTo('name')
            ->array['available_searchtypes'];

        $this->array($data[1]['available_searchtypes'])
            ->isIdenticalTo(['contains', 'notcontains', 'equals', 'notequals', 'empty']);
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
            [
                'itemtype' => 'User',
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

        $this->array($data)
            ->hasKey('headers')
            ->hasKey('totalcount')
            ->hasKey('count')
            ->hasKey('sort')
            ->hasKey('order')
            ->hasKey('rawdata');

        $headers = $data['headers'];

        $this->array($data['headers'])->hasKey('Accept-Range');

        $this->string($headers['Accept-Range'][0])->startWith('User');

        $this->array($data['rawdata'])->hasSize(9);

        $first_user = array_shift($data['data']);
        $second_user = array_shift($data['data']);

        $this->array($first_user)->hasKey(81);
        $this->array($second_user)->hasKey(81);

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
            [
                'itemtype' => 'User',
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

        $this->variable($data)->isNotFalse();

        $this->array($data)
            ->hasKey('totalcount')
            ->hasKey('count')
            ->hasKey('sort')
            ->hasKey('order')
            ->hasKey('rawdata');

        $this->array($data['rawdata'])->hasSize(9);

        $first_user = array_shift($data['data']);
        $second_user = array_shift($data['data']);
        $this->array($first_user)->hasKey(81);
        $this->array($second_user)->hasKey(81);

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
            [
                'itemtype' => 'User',
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

        $this->variable($data)->isNotFalse();

        $this->array($data)
            ->hasKey('headers')
            ->hasKey('totalcount')
            ->hasKey('count')
            ->hasKey('sort')
            ->hasKey('order')
            ->hasKey('rawdata');

        $this->array($data['headers'])->hasKey('Accept-Range');

        $this->string($data['headers']['Accept-Range'][0])->startWith('User');

        $this->array($data['rawdata'])->hasSize(9);

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

        // test a non existing search option ID
        $this->query(
            'search',
            [
                'itemtype' => 'User',
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

        // test a non numeric search option ID
        $this->query(
            'search',
            [
                'itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'reset'    => 'reset',
                    'criteria' => [
                        [
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
            [
                'itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'reset'    => 'reset',
                    'criteria' => [
                        [
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
            [
                'verb'     => 'POST',
                'itemtype' => 'Computer',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => ['input' => ['name' => "My single computer "]]
            ],
            201
        );

        $this->variable($data)->isNotFalse();

        $this->array($data)
            ->hasKey('id')
            ->hasKey('message');

        $computers_id = $data['id'];
        $this->boolean(is_numeric($computers_id))->isTrue();
        $this->integer((int)$computers_id)->isGreaterThanOrEqualTo(0);

        $computer = new Computer();
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
    protected function createNetworkPort($computers_id)
    {
        $data = $this->query(
            'createItems',
            [
                'verb'     => 'POST',
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
                        'NetworkName__ipaddresses' => [
                            '-1' => "1.2.3.4",
                        ],
                        '_create_children'         => true
                    ]
                ]
            ],
            201
        );

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
    protected function createNote($computers_id)
    {
        $data = $this->query(
            'createItems',
            [
                'verb'     => 'POST',
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

        $this->variable($data)->isNotFalse();

        $this->array($data)
            ->hasKey('id')
            ->hasKey('message');
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
            [
                'verb'     => 'POST',
                'itemtype' => 'Computer',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => [
                    'input' => [
                        [
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

        $computer = new Computer();
        $this->boolean((bool)$computer->getFromDB($first_computer['id']))->isTrue();
        $this->boolean((bool)$computer->getFromDB($second_computer['id']))->isTrue();

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
            [
                'itemtype' => 'User',
                'id'       => $uid,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'expand_dropdowns' => true,
                    'with_logs'        => true
                ]
            ]
        );

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
        $data = $this->query(
            'getItem',
            [
                'itemtype' => 'Entity',
                'id'       => $eid,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['get_hateoas' => false]
            ]
        );

        $this->variable($data)->isNotFalse();
        $this->array($data)
            ->hasKey('id')
            ->hasKey('name')
            ->hasKey('completename')
            ->notHasKey('links'); // get_hateoas == false

        // Get the previously created 'computer 1'
        $data = $this->query(
            'getItem',
            [
                'itemtype' => 'Computer',
                'id'       => $computers_id,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['with_networkports' => true]
            ]
        );

        $this->variable($data)->isNotFalse();

        $this->array($data)
            ->hasKey('id')
            ->hasKey('name')
            ->hasKey('_networkports');

        $this->array($data['_networkports'])->hasKey('NetworkPortEthernet');
        $this->array($data['_networkports']['NetworkPortEthernet'])->isEmpty();

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

        $this->variable($data)->isNotFalse();

        $this->array($data)
         ->hasKey('id')
         ->hasKey('name')
         ->hasKey('_networkports');

        $this->array($data['_networkports'])->hasKey('NetworkPortEthernet');
        $this->array($data['_networkports']['NetworkPortEthernet'])->isNotEmpty();

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
    public function testGetItemWithNotes()
    {
        $computer = $this->createComputer();
        $computers_id = $computer->getID();

        // try to create a new note
        $this->createNote($computers_id);

        // Get the previously created 'computer 1'
        $data = $this->query(
            'getItem',
            [
                'itemtype' => 'Computer',
                'id'       => $computers_id,
                'headers'  => ['Session-Token'     => $this->session_token],
                'query'    => ['with_notes' => true]
            ]
        );

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
    public function testGetItems()
    {
        // test retrieve all users
        $data = $this->query(
            'getItems',
            [
                'itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'expand_dropdowns' => true
                ]
            ]
        );

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
        $data = $this->query(
            'getItems',
            [
                'itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'range' => '0-1',
                    'expand_dropdowns' => true
                ]
            ],
            206
        );

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
        $data = $this->query(
            'getItems',
            [
                'itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['searchText' => ['name' => 'gl']]
            ]
        );

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
        $data = $this->query(
            'getItems',
            [
                'itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['only_id' => true]
            ]
        );

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
        $data = $this->query(
            'getItems',
            [
                'itemtype' => 'Config',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['expand_dropdowns' => true]
            ],
            206
        );

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
    public function testgetItemsInvalidRange()
    {
        $this->query(
            'getItems',
            [
                'itemtype' => 'User',
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
            [
                'query' => [
                    'login'    => 'post-only',
                    'password' => 'postonly'
                ]
            ]
        );

        // create a ticket for another user (glpi - super-admin)
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name'                => 'test post-only',
            'content'             => 'test post-only',
            '_users_id_requester' => 2
        ]);
        $this->integer((int)$tickets_id)->isGreaterThan(0);

        // try to access this ticket with post-only
        $this->query(
            'getItem',
            [
                'itemtype' => 'Ticket',
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
            [
                'itemtype' => 'Ticket',
                'headers'  => ['Session-Token' => $data['session_token'],],
                'query'    => [
                    'expand_dropdowns' => true
                ]
            ]
        );

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
    public function testUpdateItem()
    {
        $computer = $this->createComputer();
        $computers_id = $computer->getID();

        $data = $this->query(
            'updateItems',
            [
                'itemtype' => 'Computer',
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

        $this->variable($data)->isNotFalse();

        $computer = array_shift($data);
        $this->array($computer)
            ->hasKey($computers_id)
            ->hasKey('message');
        $this->boolean((bool)$computer[$computers_id])->isTrue();

        $computer = new Computer();
        $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();
        $this->string($computer->fields['serial'])->isIdenticalTo('abcdef');
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
        foreach ($computers_id_collection as $computers_id) {
            $input[] = [
                'id'          => $computers_id['id'],
                'otherserial' => "abcdef"
            ];
        }
        $data = $this->query(
            'updateItems',
            [
                'itemtype' => 'Computer',
                'verb'     => 'PUT',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => ['input' => $input]
            ]
        );

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
    public function testDeleteItem()
    {
        $eid = getItemByTypeName('Entity', '_test_root_entity', true);
        $_SESSION['glpiactive_entity'] = $eid;
        $computer = new \Computer();
        $this->integer(
            $computer->add([
                'name'         => 'A computer to delete',
                'entities_id'  => $eid
            ])
        )->isGreaterThan(0);
        $computers_id = $computer->getID();

        $data = $this->query(
            'deleteItems',
            [
                'itemtype' => 'Computer',
                'id'       => $computers_id,
                'verb'     => 'DELETE',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['force_purge' => "true"]
            ]
        );

        $this->variable($data)->isNotFalse();

        $this->array($data)->hasKey('headers');
        unset($data['headers']);
        $computer = array_shift($data);
        $this->array($computer)
            ->hasKey($computers_id)
            ->hasKey('message');

        $computer = new \Computer();
        $this->boolean((bool)$computer->getFromDB($computers_id))->isFalse();
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
        foreach ($computers_id_collection as $computers_id) {
            $input[] = ['id' => $computers_id['id']];
        }
        $data = $this->query(
            'deleteItems',
            [
                'itemtype' => 'Computer',
                'verb'     => 'DELETE',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => [
                    'input'       => $input,
                    'force_purge' => true
                ]
            ]
        );

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
        foreach ($computers_id_collection as $computers_id) {
            $input[] = ['id' => $computers_id['id']];
        }
        $data = $this->query(
            'deleteItems',
            [
                'itemtype' => 'Computer',
                'verb'     => 'DELETE',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => [
                    'input'       => $input,
                    'force_purge' => true
                ]
            ],
            207
        );

        $this->variable($data)->isNotFalse();
        $this->boolean($data[1][0][$computers_id_collection[0]['id']])->isTrue();
        $this->array($data[1][0])->hasKey('message');
        $this->boolean($data[1][1][$computers_id_collection[1]['id']])->isFalse();
        $this->array($data[1][1])->hasKey('message');
    }

    /**
     * @tags    api
     */
    public function testInjection()
    {
        $data = $this->query(
            'createItems',
            [
                'itemtype' => 'Computer',
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

        $this->array($data)->hasKey('id');
        $new_id = $data['id'];

        $computer = new Computer();
        $this->boolean((bool)$computer->getFromDB($new_id))->isTrue();

        //Add SQL injection spotted!
        $this->boolean($computer->fields['otherserial'] != 'Not hacked')->isFalse();

        $data = $this->query(
            'updateItems',
            [
                'itemtype' => 'Computer',
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

        $this->boolean((bool)$computer->getFromDB($new_id))->isTrue();
        //Update SQL injection spotted!
        $this->boolean($computer->fields['otherserial'] === 'injected')->isFalse();

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

        // set a non empty value to the sessionts to check
        foreach ($sensitiveSettings as $name) {
            Config::setConfigurationValues('core', [$name => 'not_empty_password']);
            $value = Config::getConfigurationValues('core', [$name]);
            $this->array($value)->hasKey($name);
            $this->string($value[$name])->isNotEmpty();
        }

        $config = new Config();
        $rows = $config->find(['context' => 'core', 'name' => $sensitiveSettings]);
        $this->array($rows)->hasSize(count($sensitiveSettings));

        // Check the value is not retrieved for sensitive settings
        foreach ($rows as $row) {
            $data = $this->query(
                'getItem',
                [
                    'itemtype' => 'Config',
                    'id'       => $row['id'],
                    'headers' => ['Session-Token' => $this->session_token]
                ]
            );
            $this->array($data)->notHasKey('value');
        }

        // Check an other setting is disclosed (when not empty)
        $config = new Config();
        $config->getFromDBByCrit(['context' => 'core', 'name' => 'admin_email']);
        $data = $this->query(
            'getItem',
            [
                'itemtype' => 'Config',
                'id'       => $config->getID(),
                'headers' => ['Session-Token' => $this->session_token]
            ]
        );

        $this->variable($data['value'])->isNotEqualTo('');

        // Check a search does not disclose sensitive values
        $data = $this->query(
            'search',
            [
                'itemtype' => 'Config',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => []
            ],
            206
        );
        foreach ($data['data'] as $row) {
            foreach ($row as $col) {
                $this->variable($col)->isNotEqualTo('not_empty_password');
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

        $obj = new Item_DeviceSimcard();

        // Add
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $this->object($computer)->isInstanceOf('\Computer');
        $deviceSimcard = getItemByTypeName('DeviceSimcard', '_test_simcard_1');
        $this->integer((int) $deviceSimcard->getID())->isGreaterThan(0);
        $this->object($deviceSimcard)->isInstanceOf('\DeviceSimcard');
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
            [
                'itemtype' => 'Item_DeviceSimcard',
                'id'       => $id,
                'headers'  => ['Session-Token' => $limitedSessionToken]
            ]
        );
        foreach ($sensitiveFields as $field) {
            $this->array($data)->notHasKey($field);
        }

       // test getItem discloses sensitive fields when READ enabled
        $data = $this->query(
            'getItem',
            [
                'itemtype' => 'Item_DeviceSimcard',
                'id'       => $id,
                'headers'  => ['Session-Token' => $this->session_token]
            ]
        );
        foreach ($sensitiveFields as $field) {
            $this->array($data)->hasKey($field);
        }

        // test searching a sensitive field as criteria id forbidden
        $this->query(
            'search',
            [
                'itemtype' => 'Item_DeviceSimcard',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'criteria' => [
                        [
                            'field'      => 15,
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
            [
                'itemtype' => 'Item_DeviceSimcard',
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
        $this->array($data)->hasKey('cfg_glpi');
        $this->array($data['cfg_glpi'])->hasKey('infocom_types');
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

            $this->array($itemtype::$undisclosedFields)
                ->size->isGreaterThan(0);

            foreach ($itemtype::$undisclosedFields as $key) {
                $this->array($data);
                unset($data['headers']);
                foreach ($data as $item) {
                    $this->array($item)->notHasKey($key);
                }
            }
        }

        // test specific cases
        // Config
        $data = $this->query('getGlpiConfig', [
            'headers'  => ['Session-Token' => $this->session_token]
        ]);

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
        global $CFG_GLPI;

        $user = getItemByTypeName('User', TU_USER);
        $email = $user->getDefaultEmail();

        // Check that the POST method is not allowed
        $this->query(
            'lostPassword',
            ['verb' => 'POST'],
            400,
            'ERROR'
        );

        // Check that the GET method is not allowed
        $this->query(
            'lostPassword',
            ['verb' => 'GET'],
            400,
            'ERROR'
        );

        // Check that the DELETE method is not allowed
        $this->query(
            'lostPassword',
            ['verb' => 'DELETE'],
            400,
            'ERROR'
        );

        $this->array($CFG_GLPI)
            ->variable['use_notifications']->isEqualTo(0)
            ->variable['notifications_mailing']->isEqualTo(0);

        // Check that disabled notifications prevent password changes
        $this->query(
            'lostPassword',
            [
                'verb'    => 'PUT',
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

        // Test an unknown email, query will succeed to avoid exposing whether or
        // not the email actually exist in our database but there will be a
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
            [
                'verb'    => 'PATCH',
                'json'    => [
                    'email'  => $email
                ]
            ],
            200
        );

        // get the password recovery token
        $user = getItemByTypeName('User', TU_USER);
        $token = $user->fields['password_forget_token'];
        $this->string($token)->isNotEmpty();

        // Test reset password with a bad token
        $this->query(
            'lostPassword',
            [
                'verb'    => 'PUT',
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
            [
                'verb'    => 'PATCH',
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
        $this->variable($updateSuccess)->isNotFalse('password update failed');

        // Test the new password was saved
        $this->variable(\Auth::checkPassword('NewPassword', $newHash))->isNotFalse();

        // Validates that password reset token has been removed
        $user = getItemByTypeName('User', TU_USER);
        $token = $user->fields['password_forget_token'];
        $this->string($token)->isEmpty();

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
        $this->integer($data['count'])->isLessThanOrEqualTo($data['totalcount']);
        $this->array($headers)->hasKey('Content-Range');
        $expectedContentRange = '0-' . ($data['count'] - 1) . '/' . $data['totalcount'];
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
    protected function checkEmptyContentRange($data, $headers)
    {
        $this->integer($data['count'])->isLessThanOrEqualTo($data['totalcount']);
        $this->integer($data['totalcount'])->isEqualTo(0);
        $this->array($headers)->notHasKey('Content-Range');
    }

    /**
     * Check errors that are expected to happen on the API server side and thus
     * can't be caught directly from the unit tests
     *
     * @param array $expected_errors
     *
     * @return void
     */
    protected function checkServerSideError(array $expected_errors): void
    {
        $logfile = $this->getLogFilePath();
        $errors = file_get_contents($logfile);

        foreach ($expected_errors as $error) {
            $this->string($errors)->contains($error);
        }

        // Clear error file
        file_put_contents($logfile, "");
    }

    protected function doHttpRequest($verb = "get", $relative_uri = "", $params = [])
    {
        if (!empty($relative_uri)) {
            $params['headers']['Content-Type'] = "application/json";
        }
        if (isset($params['multipart'])) {
            // Guzzle lib will automatically push the correct Content-type
            unset($params['headers']['Content-Type']);
        }
        return $this->http_client->request(
            $verb,
            $this->base_uri . $relative_uri,
            $params
        );
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

        $verb = isset($params['verb']) ? $params['verb'] : 'GET';

        $resource_path  = parse_url($resource, PHP_URL_PATH);
        $resource_query = parse_url($resource, PHP_URL_QUERY);

        $relative_uri = (!in_array($resource_path, ['getItem', 'getItems', 'createItems', 'updateItems', 'deleteItems'])
                         ? $resource_path . '/'
                         : '') .
                      (isset($params['parent_itemtype'])
                         ? $params['parent_itemtype'] . '/'
                         : '') .
                      (isset($params['parent_id'])
                         ? $params['parent_id'] . '/'
                         : '') .
                      (isset($params['itemtype'])
                         ? $params['itemtype'] . '/'
                         : '') .
                      (isset($params['id'])
                         ? $params['id']
                         : '') .
                      (!empty($resource_query)
                         ? '?' . $resource_query
                         : '');

        $expected_errors = $params['server_errors'] ?? [];

        unset(
            $params['itemtype'],
            $params['id'],
            $params['parent_itemtype'],
            $params['parent_id'],
            $params['verb'],
            $params['server_errors']
        );
        // launch query
        try {
            $res = $this->doHttpRequest($verb, $relative_uri, $params);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
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
        $this->checkServerSideError($expected_errors);
        return $data;
    }

    /**
     * @tags   api
     * @covers API::cors
     **/
    public function testCORS()
    {
        $res = $this->doHttpRequest(
            'OPTIONS',
            '',
            [
                'headers' => [
                    'Origin' => "http://localhost",
                    'Access-Control-Request-Method'  => 'GET',
                    'Access-Control-Request-Headers' => 'X-Requested-With'
                ]
            ]
        );

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
            ->contains('authorization')
            ->contains('app-token');
    }

    /**
     * @tags   api
     * @covers API::inlineDocumentation
     **/
    public function testInlineDocumentation()
    {
        $res = $this->doHttpRequest('GET');
        $this->variable($res)->isNotNull();
        $this->variable($res->getStatusCode())->isEqualTo(200);
        $headers = $res->getHeaders();
        $this->array($headers)->hasKey('Content-Type');
        $this->string($headers['Content-Type'][0])->isIdenticalTo('text/html; charset=UTF-8');

        // FIXME Remove this when deprecation notices will be fixed on michelf/php-markdown side
        $file_updated = file_put_contents($this->getLogFilePath(), "");
    }

    /**
     * @tags   api
     * @covers API::initSession
     **/
    public function initSessionCredentials()
    {
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
    public function testInitSessionUserToken()
    {
        $uid = getItemByTypeName('User', TU_USER, true);

        // generate a new api token TU_USER user
        global $DB;
        $token = \User::getUniqueToken('api_token');
        $updated = $DB->update(
            'glpi_users',
            [
                'api_token' => $token,
            ],
            ['id' => $uid]
        );
        $this->boolean($updated)->isTrue();

        $res = $this->doHttpRequest(
            'GET',
            'initSession?get_full_session=true',
            [
                'headers' => [
                    'Authorization' => "user_token $token"
                ]
            ]
        );

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
    public function testBadEndpoint()
    {
        $this->query(
            'badEndpoint',
            [
                'headers' => [
                    'Session-Token' => $this->session_token
                ]
            ],
            400,
            'ERROR_RESOURCE_NOT_FOUND_NOR_COMMONDBTM'
        );

        $this->query(
            'getItems',
            [
                'itemtype'        => 'badEndpoint',
                'parent_id'       => 0,
                'parent_itemtype' => 'Entity',
                'headers'         => [
                    'Session-Token' => $this->session_token
                ]
            ],
            400,
            'ERROR_RESOURCE_NOT_FOUND_NOR_COMMONDBTM'
        );
    }

    /**
     * @tags    api
     */
    public function testUpdateItemWithIdInQueryString()
    {
        $computer = $this->createComputer();
        $computers_id = $computer->getID();

        $data = $this->query(
            'updateItems',
            [
                'itemtype' => 'Computer',
                'id'       => $computers_id,
                'verb'     => 'PUT',
                'headers'  => [
                    'Session-Token' => $this->session_token
                ],
                'json'     => [
                    'input' => [
                        'serial' => "abcdefg"
                    ]
                ]
            ]
        );

        $this->variable($data)->isNotFalse();

        $this->array($data)->hasKey('headers');
        unset($data['headers']);

        $computer = array_shift($data);
        $this->array($computer)
            ->hasKey($computers_id)
            ->hasKey('message');
        $this->boolean((bool)$computer[$computers_id])->isTrue();

        $computer = new \Computer();
        $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();
        $this->string($computer->fields['serial'])->isIdenticalTo('abcdefg');
    }


    /**
     * @tags    api
     */
    public function testUploadDocument()
    {
        // we will try to upload the README.md file
        $document_name = "My document uploaded by api";
        $filename      = "README.md";
        $filecontent   = file_get_contents($filename);

        $data = $this->query(
            'createItems',
            [
                'verb'      => 'POST',
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
                ]
            ],
            201
        );

        $this->array($data)
            ->hasKey('id')
            ->hasKey('message');
        $documents_id = $data['id'];
        $this->boolean(is_numeric($documents_id))->isTrue();
        $this->integer((int)$documents_id)->isGreaterThan(0);

        $document = new \Document();
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
    public function testUpdateItemWithNoInput()
    {
        //try to update an item without input
        $this->query(
            'updateItems',
            [
                'itemtype' => 'Computer',
                'verb'     => 'PUT',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => []
            ],
            400,
            'ERROR_JSON_PAYLOAD_INVALID'
        );
    }

    /**
     * @tags    api
     * @covers  API::getItems
     */
    public function testGetItemsCommonDBChild()
    {
        // test the case have DBChild not have entities_id
        $ticketTemplate = new TicketTemplate();
        $ticketTMF = new TicketTemplateMandatoryField();

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

        $data = $this->query(
            'getItems',
            [
                'query'     => [
                    'searchText' => ['tickettemplates_id' => "^" . $tt_id . "$"]
                ],
                'itemtype'   => 'TicketTemplateMandatoryField',
                'headers'    => ['Session-Token' => $this->session_token]
            ],
            200
        );
        if (isset($data['headers'])) {
            unset($data['headers']);
        }
        $this->integer(count($data))->isEqualTo(1);
    }

    /**
     * @tags   api
     * @covers API::userPicture
     */
    public function testUserPicture()
    {
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

    protected function deprecatedProvider()
    {
        return [
            ['provider' => TicketFollowup::class],
            ['provider' => Computer_SoftwareVersion::class],
            ['provider' => Computer_SoftwareLicense::class],
            ['provider' => ComputerAntivirus::class],
            ['provider' => ComputerVirtualMachine::class],
            ['provider' => Computer_Item::class],
        ];
    }

    /**
     * @dataProvider deprecatedProvider
     */
    public function testDeprecatedGetItem(string $provider)
    {
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
        $item->delete(['id' => $item_id], true);
    }

    /**
     * @dataProvider deprecatedProvider
     */
    public function testDeprecatedGetItems(string $provider)
    {
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
        $item->delete(['id' => $item_id], true);
    }

    /**
     * @dataProvider deprecatedProvider
     */
    public function testDeprecatedCreateItems(string $provider)
    {
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
        $item->delete(['id' => $data['id']], true);
    }

    /**
     * @dataProvider deprecatedProvider
     */
    public function testDeprecatedUpdateItems(string $provider)
    {
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
        $item->delete(['id' => $item_id], true);
    }

    /**
     * @dataProvider deprecatedProvider
     */
    public function testDeprecatedDeleteItems(string $provider)
    {
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
        $this->query("$deprecated_itemtype/$item_id?force_purge=1", [
            'headers' => $headers,
            'verb'    => "DELETE",
        ], 200, "", true);

        $this->boolean($item->getFromDB($item_id))->isFalse();
    }

    /**
     * @dataProvider deprecatedProvider
     */
    public function testDeprecatedListSearchOptions(string $provider)
    {
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
        $this->array($data)->isEqualTo(json_decode($expected, true));
    }

    /**
     * @dataProvider deprecatedProvider
     */
    public function testDeprecatedSearch(string $provider)
    {
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


    protected function testGetMassiveActionsProvider(): array
    {
        // Create a computer with "is_deleted = 1" for our tests
        $computer = new Computer();
        $deleted_computers_id = $computer->add([
            'name' => 'test deleted PC',
            'entities_id' => getItemByTypeName("Entity", '_test_root_entity', true)
        ]);
        $this->integer($deleted_computers_id)->isGreaterThan(0);
        $this->boolean($computer->delete(['id' => $deleted_computers_id]))->isTrue();
        $this->boolean($computer->getFromDB($deleted_computers_id))->isTrue();
        $this->integer($computer->fields['is_deleted'])->isEqualTo(1);

        return [
            [
                'url' => 'getMassiveActions/Computersefjhfs',
                'status' => 400,
                'response' => [],
                'error' => "ERROR_RESOURCE_NOT_FOUND_NOR_COMMONDBTM",
            ],
            [
                'url' => 'getMassiveActions/Computer/40000000',
                'status' => 400,
                'response' => [],
                'error' => "ERROR_ITEM_NOT_FOUND",
            ],
            [
                'url' => 'getMassiveActions/Computer',
                'status' => 200,
                'response' => [
                    ["key" => "MassiveAction:update",            "label" => "Update"],
                    ["key" => "MassiveAction:clone",             "label" => "Clone"],
                    ["key" => "Item_Line:add",                   "label" => "Add a line"],
                    ["key" => "Item_Line:remove",                "label" => "Remove a line"],
                    ["key" => "Infocom:activate",                "label" => "Enable the financial and administrative information"],
                    ["key" => "MassiveAction:delete",            "label" => "Put in trashbin"],
                    ["key" => "ObjectLock:unlock",               "label" => "Unlock items"],
                    ["key" => "Appliance:add_item",              "label" => "Associate to an appliance"],
                    ["key" => "Item_Rack:delete",                "label" => "Remove from a rack"],
                    ["key" => "Item_OperatingSystem:update",     "label" => "Operating systems"],
                    ["key" => "Glpi\\Asset\\Asset_PeripheralAsset:add", "label" => "Connect"],
                    ["key" => "Item_SoftwareVersion:add",        "label" => "Install"],
                    ["key" => "Item_SoftwareLicense:add",        "label" => "Add a license"],
                    ["key" => "Domain:add_item",                 "label" => "Add a domain"],
                    ["key" => "Domain:remove_domain",            "label" => "Remove a domain"],
                    ["key" => "KnowbaseItem_Item:add",           "label" => "Link knowledgebase article"],
                    ["key" => "Document_Item:add",               "label" => "Add a document"],
                    ["key" => "Document_Item:remove",            "label" => "Remove a document"],
                    ["key" => "Contract_Item:add",               "label" => "Add a contract"],
                    ["key" => "Contract_Item:remove",            "label" => "Remove a contract"],
                    ["key" => "Reservation:enable",              "label" => "Authorize reservations"],
                    ["key" => "Reservation:disable",             "label" => "Prohibit reservations"],
                    ["key" => "Reservation:available",           "label" => "Make available for reservations"],
                    ["key" => "Reservation:unavailable",         "label" => "Make unavailable for reservations"],
                    ["key" => "MassiveAction:amend_comment",     "label" => "Amend comment"],
                    ["key" => "MassiveAction:add_note",          "label" => "Add note"],
                    ["key" => "Lock:unlock_component",           "label" => "Unlock components"],
                    ["key" => "Lock:unlock_fields",              "label" => "Unlock fields"],
                ],
            ],
            [
                'url' => 'getMassiveActions/Computer?is_deleted=1',
                'status' => 200,
                'response' => [
                    ["key" => "MassiveAction:purge_item_but_devices",  "label" => "Delete permanently but keep devices"],
                    ["key" => "MassiveAction:purge",                   "label" => "Delete permanently and remove devices"],
                    ["key" => "MassiveAction:restore",                 "label" => "Restore"],
                    ["key" => "Lock:unlock_component",           "label" => "Unlock components"],
                    ["key" => "Lock:unlock_fields",              "label" => "Unlock fields"],
                ],
            ],
            [
                'url' => 'getMassiveActions/Computer/' . getItemByTypeName("Computer", '_test_pc01', true),
                'status' => 200,
                'response' => [
                    ["key" => "MassiveAction:update",            "label" => "Update"],
                    ["key" => "MassiveAction:clone",             "label" => "Clone"],
                    ["key" => "Item_Line:add",                   "label" => "Add a line"],
                    ["key" => "Item_Line:remove",                "label" => "Remove a line"],
                    ["key" => "Infocom:activate",                "label" => "Enable the financial and administrative information"],
                    ["key" => "MassiveAction:delete",            "label" => "Put in trashbin"],
                    ["key" => "ObjectLock:unlock",               "label" => "Unlock items"],
                    ["key" => "Appliance:add_item",              "label" => "Associate to an appliance"],
                    ["key" => "Item_Rack:delete",                "label" => "Remove from a rack"],
                    ["key" => "Item_OperatingSystem:update",     "label" => "Operating systems"],
                    ["key" => "Glpi\\Asset\\Asset_PeripheralAsset:add", "label" => "Connect"],
                    ["key" => "Item_SoftwareVersion:add",        "label" => "Install"],
                    ["key" => "Item_SoftwareLicense:add",        "label" => "Add a license"],
                    ["key" => "Domain:add_item",                 "label" => "Add a domain"],
                    ["key" => "Domain:remove_domain",            "label" => "Remove a domain"],
                    ["key" => "KnowbaseItem_Item:add",           "label" => "Link knowledgebase article"],
                    ["key" => "Document_Item:add",               "label" => "Add a document"],
                    ["key" => "Document_Item:remove",            "label" => "Remove a document"],
                    ["key" => "Contract_Item:add",               "label" => "Add a contract"],
                    ["key" => "Contract_Item:remove",            "label" => "Remove a contract"],
                    ["key" => "Reservation:enable",              "label" => "Authorize reservations"],
                    ["key" => "Reservation:disable",             "label" => "Prohibit reservations"],
                    ["key" => "Reservation:available",           "label" => "Make available for reservations"],
                    ["key" => "Reservation:unavailable",         "label" => "Make unavailable for reservations"],
                    ["key" => "MassiveAction:amend_comment",     "label" => "Amend comment"],
                    ["key" => "MassiveAction:add_note",          "label" => "Add note"],
                    ["key" => "Lock:unlock_component",           "label" => "Unlock components"],
                    ["key" => "Lock:unlock_fields",              "label" => "Unlock fields"],
                ],
            ],
            [
                'url' => "getMassiveActions/Computer/$deleted_computers_id",
                'status' => 200,
                'response' => [
                    ["key" => "MassiveAction:purge_item_but_devices",  "label" => "Delete permanently but keep devices"],
                    ["key" => "MassiveAction:purge",                   "label" => "Delete permanently and remove devices"],
                    ["key" => "MassiveAction:restore",                 "label" => "Restore"],
                    ["key" => "Lock:unlock_component",                 "label" => "Unlock components"],
                    ["key" => "Lock:unlock_fields",                    "label" => "Unlock fields"],
                ],
            ],
        ];
    }

    /**
     * Tests for the "getMassiveActions" endpoint
     *
     * @dataProvider testGetMassiveActionsProvider
     */
    public function testGetMassiveActions(
        string $url,
        int $status,
        ?array $response,
        string $error = ""
    ): void {
        $headers = ['Session-Token' => $this->session_token];
        $data    = $this->query($url, [
            'headers' => $headers,
        ], $status, $error);

        // If no errors are expected, check results
        if (empty($error)) {
            unset($data['headers']);
            $this->array($data)->isEqualTo($response);
        }
    }

    protected function testGetMassiveActionParametersProvider(): array
    {
        return [
            [
                'url' => 'getMassiveActionParameters/Computer',
                'status' => 400,
                'response' => [],
                'error' => "ERROR_MASSIVEACTION_KEY"
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/MassiveAction:doesnotexist',
                'status' => 400,
                'response' => [],
                'error' => "ERROR_MASSIVEACTION_KEY"
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/MassiveAction:update',
                'status' => 200,
                'response' => [],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/MassiveAction:clone',
                'status' => 200,
                'response' => [
                    ["name" => "nb_copy", "type" => "number"],
                ],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/Infocom:activate',
                'status' => 200,
                'response' => [],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/MassiveAction:delete',
                'status' => 200,
                'response' => [],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/ObjectLock:unlock',
                'status' => 200,
                'response' => [],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/Appliance:add_item',
                'status' => 200,
                'response' => [
                    ["name" => "appliances_id", "type" => "dropdown"],
                ],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/Item_OperatingSystem:update',
                'status' => 200,
                'response' => [],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/Glpi\\Asset\\Asset_PeripheralAsset:add',
                'status' => 200,
                'response' => [
                    ["name" => "peer_itemtype_peripheral", "type" => "dropdown"],
                ],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/Item_SoftwareVersion:add',
                'status' => 200,
                'response' => [
                    ["name" => "softwares_id", "type" => "dropdown"],
                ],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/KnowbaseItem_Item:add',
                'status' => 200,
                'response' => [
                    ["name" => "peer_knowbaseitems_id", "type" => "dropdown"],
                ],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/Document_Item:add',
                'status' => 200,
                'response' => [
                    ["name" => "_rubdoc", "type" => "dropdown"],
                ],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/Document_Item:remove',
                'status' => 200,
                'response' => [
                    ["name" => "_rubdoc", "type" => "dropdown"],
                ],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/Contract_Item:add',
                'status' => 200,
                'response' => [
                    ["name" => "peer_contracts_id", "type" => "dropdown"],
                ],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/Contract_Item:remove',
                'status' => 200,
                'response' => [
                    ["name" => "peer_contracts_id", "type" => "dropdown"],
                ],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/MassiveAction:amend_comment',
                'status' => 200,
                'response' => [
                    ["name" => "amendment", "type" => "text"],
                ],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/MassiveAction:add_note',
                'status' => 200,
                'response' => [
                    ["name" => "add_note", "type" => "text"],
                ],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/Lock:unlock_component',
                'status' => 200,
                'response' => [
                    ["name" => "attached_item[]", "type" => "dropdown"],
                ],
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/Lock:unlock_fields',
                'status' => 200,
                'response' => [
                    ["name" => "attached_fields[]", "type" => "dropdown"],
                ],
            ],
        ];
    }

    /**
     * Tests for the "getMassiveActionParameters" endpoint
     *
     * @dataProvider testGetMassiveActionParametersProvider
     */
    public function testGetMassiveActionParameters(
        string $url,
        int $status,
        ?array $response,
        string $error = ""
    ): void {
        $headers = ['Session-Token' => $this->session_token];
        $data    = $this->query($url, [
            'headers' => $headers,
        ], $status, $error);

        // If no errors are expected, check results
        if (empty($error)) {
            unset($data['headers']);
            $this->array($data)->isEqualTo($response);
        }
    }

    protected function testApplyMassiveActionProvider(): array
    {
        return [
            [
                'url' => 'applyMassiveAction/Computer',
                'payload' => [
                    'ids' => [getItemByTypeName('Computer', '_test_pc01', true)],
                ],
                'status' => 400,
                'response' => [],
                'error' => "ERROR_MASSIVEACTION_KEY"
            ],
            [
                'url' => 'applyMassiveAction/Computer/MassiveAction:doesnotexist',
                'payload' => [
                    'ids' => [getItemByTypeName('Computer', '_test_pc01', true)],
                ],
                'status' => 400,
                'response' => [],
                'error' => "ERROR_MASSIVEACTION_KEY"
            ],
            [
                'url' => 'applyMassiveAction/Computer/MassiveAction:amend_comment',
                'payload' => [
                    'ids' => [],
                ],
                'status' => 400,
                'response' => [],
                'error' => "ERROR_MASSIVEACTION_NO_IDS"
            ],
            [
                'url' => 'applyMassiveAction/Computer/MassiveAction:amend_comment',
                'payload' => [
                    'ids' => [
                        getItemByTypeName('Computer', '_test_pc01', true),
                        getItemByTypeName('Computer', '_test_pc02', true)
                    ],
                    'input' => [
                        'amendment' => "newtexttoadd",
                    ],
                ],
                'status' => 200,
                'response' => [
                    'ok'       => 2,
                    'noaction' => 0,
                    'ko'       => 0,
                    'noright'  => 0,
                    'messages' => [],
                ],
                'error' => "",
                'before_test' => function () {
                    $computers = ['_test_pc01', '_test_pc02'];
                    foreach ($computers as $computer) {
                        // Init "comment" field for all targets
                        $computer = getItemByTypeName('Computer', $computer);
                        $update = $computer->update([
                            'id'      => $computer->getId(),
                            'comment' => "test comment",
                        ]);
                        $this->boolean($update)->isTrue();
                        $this->string($computer->fields['comment'])->isEqualTo("test comment");
                    }
                },
                'after_test' => function () {
                    $computers = ['_test_pc01', '_test_pc02'];
                    foreach ($computers as $computer) {
                        // Check that "comment" field was modified as expected
                        $computer = getItemByTypeName('Computer', $computer);
                        $this->string($computer->fields['comment'])->isEqualTo("test comment\n\nnewtexttoadd");
                    }
                }
            ],
            [
                'url' => 'applyMassiveAction/Computer/MassiveAction:add_note',
                'payload' => [
                    'ids' => [
                        getItemByTypeName('Computer', '_test_pc01', true),
                        getItemByTypeName('Computer', '_test_pc02', true)
                    ],
                    'input' => [
                        'add_note' => "new note",
                    ],
                ],
                'status' => 200,
                'response' => [
                    'ok'       => 2,
                    'noaction' => 0,
                    'ko'       => 0,
                    'noright'  => 0,
                    'messages' => [],
                ],
                'error' => "",
                'before_test' => function () {
                    $computers = ['_test_pc01', '_test_pc02'];
                    foreach ($computers as $computer) {
                        $note = new Notepad();
                        $existing_notes = $note->find([
                            'itemtype' => 'Computer',
                            'items_id' => getItemByTypeName('Computer', $computer, true),
                        ]);

                        // Delete all existing note for this item
                        foreach ($existing_notes as $existing_note) {
                            $deletion = $note->delete(['id' => $existing_note['id']]);
                            $this->boolean($deletion)->isTrue();
                        }

                        // Check that the items have no notes remaining
                        $this->array($note->find([
                            'itemtype' => 'Computer',
                            'items_id' => getItemByTypeName('Computer', $computer, true),
                        ]))->hasSize(0);
                    }
                },
                'after_test' => function () {
                    $computers = ['_test_pc01', '_test_pc02'];
                    foreach ($computers as $computer) {
                        $note = new Notepad();
                        $existing_notes = $note->find([
                            'itemtype' => 'Computer',
                            'items_id' => getItemByTypeName('Computer', $computer, true),
                        ]);

                        // Check that the items have one note
                        $this->array($existing_notes)->hasSize(1);

                        foreach ($existing_notes as $existing_note) {
                            $this->string($existing_note['content'])->isEqualTo("new note");
                        }
                    }
                }
            ]
        ];
    }

    /**
     * Tests for the "applyMassiveAction" endpoint
     *
     * @dataProvider testApplyMassiveActionProvider
     */
    public function testApplyMassiveAction(
        string $url,
        array $payload,
        int $status,
        ?array $response,
        string $error = "",
        ?callable $before_test = null,
        ?callable $after_test = null
    ): void {
        if (!is_null($before_test)) {
            $before_test();
        }

        $headers = ['Session-Token' => $this->session_token];
        $data    = $this->query($url, [
            'headers' => $headers,
            'verb'    => 'POST',
            'json'    => $payload,
        ], $status, $error);

        // If no errors are expected, check results
        if (empty($error)) {
            unset($data['headers']);
            $this->array($data)->isEqualTo($response);
        }

        if (!is_null($after_test)) {
            $after_test();
        }
    }

    /**
     * Data provider for testReturnSanitizedContentUnit
     *
     * @return array
     */
    protected function testReturnSanitizedContentUnitProvider(): array
    {
        return [
            [null, true],
            ["", false],
            ["true", true],
            ["false", false],
            ["on", true],
            ["off", false],
            ["1", true],
            ["0", false],
            ["yes", true],
            ["no", false],
            ["asfbhueshf", false],
        ];
    }

    /**
     * Functional test to ensure returned content is not sanitized.
     *
     * @return void
     */
    public function testContentEncoding(): void
    {
        // Get computer with encoded comment
        $computers_id = getItemByTypeName(
            "Computer",
            "_test_pc_with_encoded_comment",
            true
        );

        // Request params
        $url = "/Computer/$computers_id";
        $method = "GET";
        $headers = ['Session-Token' => $this->session_token];

        $data = $this->query($url, [
            'headers' => $headers,
            'verb'    => $method,
        ], 200);
        $this->string($data['comment'])->isEqualTo("<>");
    }

    public function test_ActorUpdate()
    {
        $headers = ['Session-Token' => $this->session_token];
        $rand = mt_rand();

        // Group used for our tests
        $groups_id = getItemByTypeName("Group", "_test_group_1", true);

        // Create ticket
        $input = [
            'input' => [
                'name' => "test_ActorUpdate_Ticket_$rand",
                'content' => 'content'
            ]
        ];
        $data = $this->query("/Ticket", [
            'headers' => $headers,
            'verb'    => "POST",
            'json'    => $input,
        ], 201);
        $this->integer($data['id'])->isGreaterThan(0);
        $tickets_id = $data['id'];

        // Add group
        $input = [
            'input' => [
                '_actors' => [
                    'assign' => [
                        [
                            'itemtype' => "Group",
                            'items_id' => $groups_id,
                            'use_notification' => 1,
                        ]
                    ]
                ]
            ]
        ];
        $this->query("/Ticket/$tickets_id/", [
            'headers' => $headers,
            'verb'    => "PUT",
            'json'    => $input,
        ], 200);

        // Check assigned groups
        $data = $this->query("/Ticket/$tickets_id/Group_Ticket", [
            'headers' => $headers,
            'verb'    => "GET",
        ], 200);

        $this->integer($data[0]['tickets_id'])->isEqualTo($tickets_id);
        $this->integer($data[0]['groups_id'])->isEqualTo($groups_id);
    }

    /**
     * test update items endpoint
     * using application/x-www-form-urlencoded
     *
     * @return void
     */
    public function testUpdateItemFormEncodedBody()
    {
        $computer = $this->createComputer();
        $computers_id = $computer->getID();

        try {
            $response = $this->http_client->put(
                $this->base_uri . 'Computer/' . $computers_id,
                [
                    'headers' => [
                        'Session-Token' => $this->session_token,
                        'Content-Type'  => 'application/x-www-form-urlencoded',
                    ],
                    'body' => http_build_query(
                        [
                            'input' => [
                                'serial' => 'abcdefg',
                                'comment' => 'This computer has been updated.',
                            ]
                        ],
                        '',
                        '&'
                    )
                ]
            );
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
        }

        // Check response
        $this->object($response)->isInstanceOf(\Psr\Http\Message\ResponseInterface::class);
        $this->integer($response->getStatusCode())->isEqualTo(200);
        $this->object($response)->isInstanceOf(\Psr\Http\Message\ResponseInterface::class);
        $body = $response->getBody()->getContents();
        $this->array(json_decode($body, true))->isEqualTo([
            [
                (string)$computers_id => true,
                'message'             => '',
            ]
        ]);

        // Check computer is updated
        $computer = new \Computer();
        $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();
        $this->string($computer->fields['serial'])->isIdenticalTo('abcdefg');
        $this->string($computer->fields['comment'])->isIdenticalTo('This computer has been updated.');
    }

    /**
     * test delete items endpoint
     * using application/x-www-form-urlencoded
     *
     * @return void
     */
    public function testDeleteItemFormEncodedBody()
    {
        $computer = $this->createComputer();
        $computers_id = $computer->getID();

        try {
            $response = $this->http_client->delete(
                $this->base_uri . 'Computer',
                [
                    'headers' => [
                        'Session-Token' => $this->session_token,
                        'Content-Type'  => 'application/x-www-form-urlencoded',
                    ],
                    'body' => http_build_query(
                        [
                            'input' => [
                                'id' => $computers_id
                            ]
                        ]
                    )
                ]
            );
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
        }

        // Check response
        $this->object($response)->isInstanceOf(\Psr\Http\Message\ResponseInterface::class);
        $this->integer($response->getStatusCode())->isEqualTo(200);
        $this->object($response)->isInstanceOf(\Psr\Http\Message\ResponseInterface::class);
        $body = $response->getBody()->getContents();
        $this->array(json_decode($body, true))->isEqualTo([
            [
                (string)$computers_id => true,
                'message'             => '',
            ]
        ]);

        // Check computer is updated
        $computer = new \Computer();
        $this->boolean((bool)$computer->getFromDB($computers_id))->isTrue();
        $this->boolean((bool)$computer->getField('is_deleted'))->isTrue();
    }

    public function testSearchTextResponseCode()
    {
        $data = $this->query(
            'getItems',
            ['itemtype' => Computer::class,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['searchText' => ['test' => 'test']]
            ],
            400,
            'ERROR_FIELD_NOT_FOUND'
        );

        $this->variable($data)->isNotFalse();

        $data = $this->query(
            'getItems',
            ['itemtype' => Computer::class,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['searchText' => ['name' => 'test']]
            ],
            200,
        );

        $this->variable($data)->isNotFalse();
    }
}
