<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Computer;
use Config;
use Glpi\Form\Form;
use Glpi\Form\FormTranslation;
use Glpi\Form\Question;
use Glpi\Form\Section;
use Glpi\Helpdesk\HelpdeskTranslation;
use Glpi\Helpdesk\Tile\GlpiPageTile;
use Glpi\Tests\Api\Deprecated\Computer_Item;
use Glpi\Tests\Api\Deprecated\Computer_SoftwareLicense;
use Glpi\Tests\Api\Deprecated\Computer_SoftwareVersion;
use Glpi\Tests\Api\Deprecated\ComputerAntivirus;
use Glpi\Tests\Api\Deprecated\ComputerVirtualMachine;
use Glpi\Tests\Api\Deprecated\TicketFollowup;
use GuzzleHttp;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Item_DeviceSimcard;
use NetworkPort_NetworkPort;
use Notepad;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use QueuedNotification;
use TicketTemplate;
use TicketTemplateMandatoryField;

/**
 * @engine isolate
 */
class APIRestTest extends TestCase
{
    protected $session_token;
    /** @var GuzzleHttp\Client */
    protected $http_client;
    protected $base_uri = "";
    protected $last_error;

    public function setUp(): void
    {
        global $GLPI_CACHE;

        $GLPI_CACHE->clear();

        // Empty log file
        $file_updated = file_put_contents($this->getLogFilePath(), "");
        $this->assertNotSame(false, $file_updated);

        $this->http_client = new GuzzleHttp\Client();
        $this->base_uri    = trim(GLPI_URI, '/') . '/api.php/v1/';

        $this->initSessionCredentials();
        parent::setUp();
    }

    public function tearDown(): void
    {
        // Check that no errors occurred on the test server
        $this->assertEmpty(file_get_contents($this->getLogFilePath()));
        parent::tearDown();
    }

    protected function getLogFilePath(): string
    {
        return GLPI_LOG_DIR . "/php-errors.log";
    }

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
            'enable_api_login_external_token' => true,
        ]);
    }

    public function testAppToken()
    {
        $apiclient = new APIClient();
        $this->assertGreaterThan(
            0,
            (int) $apiclient->add([
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
                    'app_token' => $app_token,
                ],
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
                'app_token' => "test_invalid_token",
            ],
            ],
            400,
            'ERROR_WRONG_APP_TOKEN_PARAMETER'
        );
    }

    public function testChangeActiveEntities()
    {
        $this->query(
            'changeActiveEntities',
            ['verb'    => 'POST',
                'headers' => ['Session-Token' => $this->session_token],
                'json'    => [
                    'entities_id'   => 'all',
                    'is_recursive'  => true,
                ],
            ],
            200
        );
    }

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

    public function testChangeActiveProfile()
    {
        // test change to an existing and available profile
        $this->query(
            'changeActiveProfile',
            [
                'verb'    => 'POST',
                'headers' => ['Session-Token' => $this->session_token],
                'json'    => ['profiles_id'   => 4],
            ]
        );

        // test change to a non-existing profile
        $this->query(
            'changeActiveProfile',
            [
                'verb'    => 'POST',
                'headers' => ['Session-Token' => $this->session_token],
                'json'    => ['profiles_id'   => 9999],
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
                'json'    => ['something_bad' => 4],
            ],
            400,
            'ERROR'
        );
    }

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
        $this->assertArrayHasKey('glpilanguage', $data['session']);
        $this->assertArrayHasKey('glpilist_limit', $data['session']);
    }

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
                            'items_id' => $uid,
                        ],
                        [
                            'itemtype' => 'Entity',
                            'items_id' => $eid,
                        ],
                    ],
                    'with_logs'        => true,
                    'expand_dropdowns' => true,
                ],
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

    public function testListSearchOptions()
    {
        // test retrieve all users
        $data = $this->query(
            'listSearchOptions',
            [
                'itemtype' => 'Computer',
                'headers'  => ['Session-Token' => $this->session_token],
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
            ['contains', 'notcontains', 'equals', 'notequals', 'empty'],
            $data[1]['available_searchtypes']
        );
    }

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
                    'rawdata'       => true,
                ],
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
                    'rawdata'       => true,
                ],
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
                        ],
                    ],
                ],
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

    public function testSearchWithBadCriteria()
    {
        // test retrieve all users
        // multidimensional array of vars in query string not supported ?

        // test a non-existing search option ID
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
                    ],
                    ],
                ],
            ],
            400,   // 400 code expected (error, bad request)
            'ERROR'
        );

        // test a non-numeric search option ID
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
                        ],
                    ],
                ],
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
                        ],
                    ],
                ],
            ],
            400,  // 400 code expected (error, bad request)
            'ERROR'
        );
    }

    protected function badEndpoint($expected_code = null, $expected_symbol = null)
    {
        $this->query(
            'badEndpoint',
            [
                'headers' => [
                    'Session-Token' => $this->session_token,
                ],
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
                'json'     => ['input' => ['name' => "My single computer "]],
            ],
            201
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('message', $data);

        $computers_id = $data['id'];
        $this->assertTrue(is_numeric($computers_id));
        $this->assertGreaterThanOrEqual(0, (int) $computers_id);

        $computer = new Computer();
        $this->assertTrue((bool) $computer->getFromDB($computers_id));
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
                        '_create_children'         => true,
                    ],
                ],
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
            [
                'verb'     => 'POST',
                'itemtype' => 'Notepad',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => [
                    'input' => [
                        'itemtype' => 'Computer',
                        'items_id' => $computers_id,
                        'content'  => 'note about a computer',
                    ],
                ],
            ],
            201
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('message', $data);
    }

    public function testCreateItem()
    {
        $computer = $this->createComputer();
        $computers_id = $computer->getID();

        // create a network port for the previous computer
        $this->createNetworkPort($computers_id);

        // try to create a new note
        $this->createNote($computers_id);
    }

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
                            'name' => "My computer 2",
                        ],[
                            'name' => "My computer 3",
                        ],[
                            'name' => "My computer 4",
                        ],
                    ],
                ],
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

        $this->assertGreaterThanOrEqual(0, (int) $first_computer['id']);
        $this->assertGreaterThanOrEqual(0, (int) $second_computer['id']);

        $computer = new Computer();
        $this->assertTrue((bool) $computer->getFromDB($first_computer['id']));
        $this->assertTrue((bool) $computer->getFromDB($second_computer['id']));

        unset($data['headers']);
        return $data;
    }

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
                    'with_logs'        => true,
                ],
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
            [
                'itemtype' => 'Entity',
                'id'       => $eid,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['get_hateoas' => false],
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
            [
                'itemtype' => 'Computer',
                'id'       => $computers_id,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['with_networkports' => true],
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
                'query'    => ['with_networkports' => true],
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
                'query'    => ['with_notes' => true],
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

    public function testGetItems()
    {
        // test retrieve all users
        $data = $this->query(
            'getItems',
            [
                'itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'expand_dropdowns' => true,
                ],
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
            [
                'itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [
                    'range' => '0-1',
                    'expand_dropdowns' => true,
                ],
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
            [
                'itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['searchText' => ['name' => 'gl']],
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
            [
                'itemtype' => 'User',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['only_id' => true],
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
            [
                'itemtype' => 'Config',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['expand_dropdowns' => true],
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
                    'expand_dropdowns' => true,
                ],
            ],
            400,
            'ERROR_RANGE_EXCEED_TOTAL'
        );
    }

    /**
     * This function test https://github.com/glpi-project/glpi/issues/1103
     * A post-only user could retrieve tickets of others users when requesting itemtype
     * without first letter in uppercase
     */
    public function testgetItemsForPostonly()
    {
        // init session for postonly
        $data = $this->query(
            'initSession',
            [
                'query' => [
                    'login'    => 'post-only',
                    'password' => 'postonly',
                ],
            ]
        );

        // create a ticket for another user (glpi - super-admin)
        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name'                => 'test post-only',
            'content'             => 'test post-only',
            '_users_id_requester' => 2,
        ]);
        $this->assertGreaterThan(0, (int) $tickets_id);

        // try to access this ticket with post-only
        $this->query(
            'getItem',
            [
                'itemtype' => 'Ticket',
                'id'       => $tickets_id,
                'headers'  => [
                    'Session-Token' => $data['session_token'],
                ],
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
                    'expand_dropdowns' => true,
                ],
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        $this->assertCount(1, $data);

        // delete ticket
        $ticket->delete(['id' => $tickets_id], true);
    }

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
                        'serial' => "abcdef",
                    ],
                ],
            ]
        );

        $this->assertIsArray($data);

        $computer = array_shift($data);
        $this->assertIsArray($computer);
        $this->assertArrayHasKey($computers_id, $computer);
        $this->assertArrayHasKey('message', $computer);
        $this->assertTrue((bool) $computer[$computers_id]);

        $computer = new Computer();
        $this->assertTrue((bool) $computer->getFromDB($computers_id));
        $this->assertSame('abcdef', $computer->fields['serial']);
    }

    public function testUpdateItems()
    {
        $computers_id_collection = $this->testCreateItems();
        $input    = [];
        $computer = new Computer();
        foreach ($computers_id_collection as $computers_id) {
            $input[] = [
                'id'          => $computers_id['id'],
                'otherserial' => "abcdef",
            ];
        }
        $data = $this->query(
            'updateItems',
            [
                'itemtype' => 'Computer',
                'verb'     => 'PUT',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => ['input' => $input],
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

            $this->assertTrue((bool) $computer->getFromDB($computers_id));
            $this->assertSame('abcdef', $computer->fields['otherserial']);
        }
    }


    public function testDeleteItem()
    {
        $eid = getItemByTypeName('Entity', '_test_root_entity', true);
        $_SESSION['glpiactive_entity'] = $eid;
        $computer = new Computer();
        $this->assertGreaterThan(
            0,
            $computer->add([
                'name'         => 'A computer to delete',
                'entities_id'  => $eid,
            ])
        );
        $computers_id = $computer->getID();

        $data = $this->query(
            'deleteItems',
            [
                'itemtype' => 'Computer',
                'id'       => $computers_id,
                'verb'     => 'DELETE',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['force_purge' => "true"],
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        unset($data['headers']);
        $computer = array_shift($data);
        $this->assertIsArray($computer);
        $this->assertArrayHasKey($computers_id, $computer);
        $this->assertArrayHasKey('message', $computer);

        $computer = new Computer();
        $this->assertFalse((bool) $computer->getFromDB($computers_id));
    }


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
                    'force_purge' => true,
                ],
            ]
        );

        $this->assertIsArray($data);
        unset($data['headers']);

        foreach ($data as $index => $row) {
            $computers_id = $computers_id_collection[$index]['id'];
            $this->assertIsArray($row);
            $this->assertArrayHasKey($computers_id, $row);
            $this->assertArrayHasKey('message', $row);
            $this->assertTrue((bool) $row[$computers_id]);

            $this->assertFalse((bool) $computer->getFromDB($computers_id));
        }

        // Test multiple delete with multi-status
        $input = [];
        $computers_id_collection = [
            ['id'  => $lastComputer['id']],
            ['id'  => $lastComputer['id'] + 1], // Non existing computer id
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
                    'force_purge' => true,
                ],
            ],
            207
        );

        $this->assertIsArray($data);
        $this->assertTrue($data[1][0][$computers_id_collection[0]['id']]);
        $this->assertArrayHasKey('message', $data[1][0]);
        $this->assertFalse($data[1][1][$computers_id_collection[1]['id']]);
        $this->assertArrayHasKey('message', $data[1][1]);
    }

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
                        'otherserial' => "Not hacked",
                    ],
                ],
            ],
            201
        );

        $this->assertArrayHasKey('id', $data);
        $new_id = $data['id'];

        $computer = new Computer();
        $this->assertTrue((bool) $computer->getFromDB($new_id));

        //Add SQL injection spotted!
        $this->assertFalse($computer->fields['otherserial'] != 'Not hacked');

        $this->query(
            'updateItems',
            [
                'itemtype' => 'Computer',
                'verb'     => 'PUT',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'    => [
                    'input' => [
                        'id'     => $new_id,
                        'serial' => "abcdef', `otherserial`='injected",
                    ],
                ],
            ]
        );

        $this->assertTrue((bool) $computer->getFromDB($new_id));
        //Update SQL injection spotted!
        $this->assertFalse($computer->fields['otherserial'] === 'injected');

        $computer = new Computer();
        $computer->delete(['id' => $new_id], true);
    }

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
                [
                    'itemtype' => 'Config',
                    'id'       => $row['id'],
                    'headers' => ['Session-Token' => $this->session_token],
                ]
            );
            $this->assertArrayNotHasKey('value', $data);
        }

        // Check another setting is disclosed (when not empty)
        $config = new Config();
        $config->getFromDBByCrit(['context' => 'core', 'name' => 'admin_email']);
        $data = $this->query(
            'getItem',
            [
                'itemtype' => 'Config',
                'id'       => $config->getID(),
                'headers' => ['Session-Token' => $this->session_token],
            ]
        );

        $this->assertNotEquals('', $data['value']);

        // Check a search does not disclose sensitive values
        $data = $this->query(
            'search',
            [
                'itemtype' => 'Config',
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => [],
            ],
            206
        );
        foreach ($data['data'] as $row) {
            foreach ($row as $col) {
                $this->assertNotEquals('not_empty_password', $col);
            }
        }
    }

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
        $this->assertInstanceOf(Computer::class, $computer);
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
                'name'         => 'devicesimcard_pinpuk',
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
                'name'         => 'devicesimcard_pinpuk',
            ]
        );
        $this->session_token = $backupSessionToken;

        // test getItem does not disclose sensitive fields when READ disabled
        $data = $this->query(
            'getItem',
            [
                'itemtype' => 'Item_DeviceSimcard',
                'id'       => $id,
                'headers'  => ['Session-Token' => $limitedSessionToken],
            ]
        );
        foreach ($sensitiveFields as $field) {
            $this->assertArrayNotHasKey($field, $data);
        }

        // test getItem discloses sensitive fields when READ enabled
        $data = $this->query(
            'getItem',
            [
                'itemtype' => 'Item_DeviceSimcard',
                'id'       => $id,
                'headers'  => ['Session-Token' => $this->session_token],
            ]
        );
        foreach ($sensitiveFields as $field) {
            $this->assertArrayHasKey($field, $data);
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
                            'value'      => $input['pin'],
                        ],
                    ],
                ],
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
                    'forcedisplay'  => [15],
                ],
            ],
            400,
            'ERROR'
        );
    }

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
            'APIClient', 'AuthLDAP', 'MailCollector', 'User',
        ];
        /** @var class-string $itemtype */
        foreach ($itemtypes as $itemtype) {
            $data = $this->query(
                'getItems',
                [
                    'itemtype' => $itemtype,
                    'headers'  => ['Session-Token' => $this->session_token],
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
            'headers'  => ['Session-Token' => $this->session_token],
        ]);

        // Test undisclosed data are actually not disclosed
        $this->assertGreaterThan(0, count(Config::$undisclosedFields));
        foreach (Config::$undisclosedFields as $key) {
            $this->assertArrayNotHasKey($key, $data['cfg_glpi']);
        }
    }


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

        // Disable notifications
        Config::setConfigurationValues('core', [
            'use_notifications' => '0',
            'notifications_mailing' => '0',
        ]);

        // Check that disabled notifications prevent password changes
        $this->query(
            'lostPassword',
            [
                'verb'    => 'PUT',
                'json'    => [
                    'email'  => $email,
                ],
            ],
            400,
            'ERROR'
        );

        // Enable notifications
        Config::setConfigurationValues('core', [
            'use_notifications' => '1',
            'notifications_mailing' => '1',
        ]);

        // Test an unknown email, query will succeed to avoid exposing whether
        // the email actually exist in our database but there will be a
        // warning in the server logs
        $this->query('lostPassword', [
            'verb'    => 'PUT',
            'json'    => [
                'email'  => 'nonexistent@localhost.local',
            ],
            'server_errors' => [
                "Failed to find a single user for 'nonexistent@localhost.local', 0 user(s) found.",
            ],
        ], 200);

        // Test a valid email is accepted
        $this->query(
            'lostPassword',
            [
                'verb'    => 'PATCH',
                'json'    => [
                    'email'  => $email,
                ],
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
            [
                'verb'    => 'PUT',
                'json'    => [
                    'email'                 => $email,
                    'password_forget_token' => $token . 'bad',
                    'password'              => 'NewPassword',
                ],
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
                ],
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
        $this->assertNotFalse(Auth::checkPassword('NewPassword', $newHash));

        // Validates that password reset token has been removed
        $user = getItemByTypeName('User', TU_USER);
        $token = $user->fields['password_forget_token'];
        $this->assertEmpty($token);

        //diable notifications
        Config::setConfigurationValues('core', [
            'use_notifications' => '0',
            'notifications_mailing' => '0',
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
            $this->assertStringContainsString($error, $errors);
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

        $verb = $params['verb'] ?? 'GET';

        $resource_path  = parse_url($resource, PHP_URL_PATH);
        $resource_query = parse_url($resource, PHP_URL_QUERY);

        $relative_uri = (!in_array($resource_path, ['getItem', 'getItems', 'createItems', 'updateItems', 'deleteItems'])
                         ? $resource_path . '/'
                         : '')
                      . (isset($params['parent_itemtype'])
                         ? $params['parent_itemtype'] . '/'
                         : '')
                      . (isset($params['parent_id'])
                         ? $params['parent_id'] . '/'
                         : '')
                      . (isset($params['itemtype'])
                         ? $params['itemtype'] . '/'
                         : '')
                      . ($params['id']
                         ?? '')
                      . (!empty($resource_query)
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
        } catch (ClientException $e) {
            $response = $e->getResponse();
            if (!in_array($response->getStatusCode(), $expected_codes)) {
                //throw exceptions not expected
                throw $e;
            }
            $this->assertContains($response->getStatusCode(), $expected_codes);
            $body = json_decode($e->getResponse()->getBody());
            $this->assertIsArray($body);
            $this->assertArrayHasKey('0', $body);
            $this->assertSame($expected_symbol, $body[0]);
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
        $this->assertNotNull($res);
        $this->assertContains($res->getStatusCode(), $expected_codes);
        $this->checkServerSideError($expected_errors);
        return $data;
    }

    public function testCORS()
    {
        $res = $this->doHttpRequest(
            'OPTIONS',
            '',
            [
                'headers' => [
                    'Origin' => "http://localhost",
                    'Access-Control-Request-Method'  => 'GET',
                    'Access-Control-Request-Headers' => 'X-Requested-With',
                ],
            ]
        );

        $this->assertNotNull($res);
        $this->assertEquals(200, $res->getStatusCode());
        $headers = $res->getHeaders();
        $this->assertArrayHasKey('Access-Control-Allow-Methods', $headers);
        $this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);

        $this->assertStringContainsString('GET', $headers['Access-Control-Allow-Methods'][0]);
        $this->assertStringContainsString('PUT', $headers['Access-Control-Allow-Methods'][0]);
        $this->assertStringContainsString('POST', $headers['Access-Control-Allow-Methods'][0]);
        $this->assertStringContainsString('DELETE', $headers['Access-Control-Allow-Methods'][0]);
        $this->assertStringContainsString('OPTIONS', $headers['Access-Control-Allow-Methods'][0]);

        $this->assertStringContainsString('origin', $headers['Access-Control-Allow-Headers'][0]);
        $this->assertStringContainsString('content-type', $headers['Access-Control-Allow-Headers'][0]);
        $this->assertStringContainsString('accept', $headers['Access-Control-Allow-Headers'][0]);
        $this->assertStringContainsString('session-token', $headers['Access-Control-Allow-Headers'][0]);
        $this->assertStringContainsString('authorization', $headers['Access-Control-Allow-Headers'][0]);
        $this->assertStringContainsString('app-token', $headers['Access-Control-Allow-Headers'][0]);
    }

    public function testInlineDocumentation()
    {
        $res = $this->doHttpRequest('GET');
        $this->assertNotNull($res);
        $this->assertEquals(200, $res->getStatusCode());
        $headers = $res->getHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertSame('text/html; charset=UTF-8', $headers['Content-Type'][0]);

        // FIXME Remove this when deprecation notices will be fixed on michelf/php-markdown side
        $file_updated = file_put_contents($this->getLogFilePath(), "");
    }

    public function initSessionCredentials()
    {
        $res = $this->doHttpRequest('GET', 'initSession/', ['auth' => [TU_USER, TU_PASS]]);

        $this->assertNotNull($res);
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertContains('application/json; charset=UTF-8', $res->getHeader('content-type'));

        $body = $res->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('session_token', $data);
        $this->session_token = $data['session_token'];
    }

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
        $this->assertTrue($updated);

        $res = $this->doHttpRequest(
            'GET',
            'initSession?get_full_session=true',
            [
                'headers' => [
                    'Authorization' => "user_token $token",
                ],
            ]
        );

        $this->assertNotNull($res);
        $this->assertEquals(200, $res->getStatusCode());

        $body = $res->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('session_token', $data);
        $this->assertArrayHasKey('session', $data);
        $this->assertEquals($uid, $data['session']['glpiID']);
    }

    public function testBadEndpoint()
    {
        $this->query(
            'badEndpoint',
            [
                'headers' => [
                    'Session-Token' => $this->session_token,
                ],
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
                    'Session-Token' => $this->session_token,
                ],
            ],
            400,
            'ERROR_RESOURCE_NOT_FOUND_NOR_COMMONDBTM'
        );
    }

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
                    'Session-Token' => $this->session_token,
                ],
                'json'     => [
                    'input' => [
                        'serial' => "abcdefg",
                    ],
                ],
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        unset($data['headers']);

        $computer = array_shift($data);
        $this->assertIsArray($computer);
        $this->assertArrayHasKey($computers_id, $computer);
        $this->assertArrayHasKey('message', $computer);
        $this->assertTrue((bool) $computer[$computers_id]);

        $computer = new Computer();
        $this->assertTrue((bool) $computer->getFromDB($computers_id));
        $this->assertSame('abcdefg', $computer->fields['serial']);
    }


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
                    'Session-Token' => $this->session_token,
                ],
                'multipart' => [
                    // the document part
                    [
                        'name'     => 'uploadManifest',
                        'contents' => json_encode([
                            'input' => [
                                'name'       => $document_name,
                                '_filename'  => [$filename],
                            ],
                        ]),
                    ],
                    // the FILE part
                    [
                        'name'     => 'filename[]',
                        'contents' => $filecontent,
                        'filename' => $filename,
                    ],
                ],
            ],
            201
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('message', $data);
        $documents_id = $data['id'];
        $this->assertTrue(is_numeric($documents_id));
        $this->assertGreaterThan(0, (int) $documents_id);

        $document = new \Document();
        $this->assertTrue((bool) $document->getFromDB($documents_id));

        $this->assertIsArray($document->fields);
        $this->assertSame('text/plain', $document->fields['mime']);
        $this->assertSame($document_name, $document->fields['name']);
        $this->assertSame($filename, $document->fields['filename']);

        $this->assertStringContainsString('MD/', $document->fields['filepath']);
    }

    public function testUpdateItemWithNoInput()
    {
        //try to update an item without input
        $this->query(
            'updateItems',
            [
                'itemtype' => 'Computer',
                'verb'     => 'PUT',
                'headers'  => ['Session-Token' => $this->session_token],
                'json'     => [],
            ],
            400,
            'ERROR_BAD_ARRAY'
        );
    }

    public function testGetItemWithContacts()
    {
        $computers = $this->createComputers(['Computer 1', 'Computer 2']);
        $this->assertComputersCreated($computers);

        $networkports = [];
        foreach ($computers as $computer_id) {
            $this->assertComputerNetworkPorts($computer_id, true);
            $this->createNetworkPort($computer_id);
            $networkports[] = $this->assertComputerNetworkPorts($computer_id, false);
        }

        $this->linkNetworkPorts($networkports);
        $this->assertNetworkPortLink($computers, $networkports);
    }

    private function createComputers(array $names)
    {
        $data = $this->query(
            'createItems',
            [
                'verb' => 'POST',
                'itemtype' => 'Computer',
                'headers' => ['Session-Token' => $this->session_token],
                'json' => ['input' => array_map(fn($name) => ['name' => $name], $names)],
            ],
            201
        );

        $this->assertIsArray($data);
        return array_column($data, 'id');
    }

    private function assertComputersCreated(array $computers)
    {
        foreach ($computers as $computer_id) {
            $computer = new Computer();
            $this->assertTrue((bool) $computer->getFromDB($computer_id));
        }
    }

    private function assertComputerNetworkPorts($computer_id, $shouldBeEmpty)
    {
        $data = $this->query(
            'getItem',
            [
                'itemtype' => 'Computer',
                'id' => $computer_id,
                'headers' => ['Session-Token' => $this->session_token],
                'query' => ['with_networkports' => true],
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('_networkports', $data);
        $this->assertArrayHasKey('NetworkPortEthernet', $data['_networkports']);
        if ($shouldBeEmpty) {
            $this->assertEmpty($data['_networkports']['NetworkPortEthernet']);
            return null;
        } else {
            $this->assertNotEmpty($data['_networkports']['NetworkPortEthernet']);
            $networkport = $data['_networkports']['NetworkPortEthernet'][0];
            $this->assertArrayHasKey('NetworkName', $networkport);
            $this->assertArrayHasKey('networkports_id_opposite', $networkport);
            $this->assertArrayHasKey('netport_id', $networkport);
            $this->assertNull($networkport['networkports_id_opposite']);
            $this->assertGreaterThan(0, $networkport['netport_id']);
            return $networkport['netport_id'];
        }
    }

    private function linkNetworkPorts(array $networkports)
    {
        $data = $this->query(
            'createItems',
            [
                'verb' => 'POST',
                'itemtype' => 'NetworkPort_NetworkPort',
                'headers' => ['Session-Token' => $this->session_token],
                'json' => [
                    'input' => [
                        [
                            'networkports_id_1' => $networkports[0],
                            'networkports_id_2' => $networkports[1],
                        ],
                    ],
                ],
            ],
            201
        );

        $this->assertIsArray($data);
        $data = $data[0];
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertGreaterThan(0, (int) $data['id']);

        $networkport_networkport = new NetworkPort_NetworkPort();
        $this->assertTrue((bool) $networkport_networkport->getFromDB($data['id']));
    }

    private function assertNetworkPortLink(array $computers, array $networkports)
    {
        $data = $this->query(
            'getItem',
            [
                'itemtype' => 'Computer',
                'id' => $computers[0],
                'headers' => ['Session-Token' => $this->session_token],
                'query' => ['with_networkports' => true],
            ]
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('_networkports', $data);
        $this->assertArrayHasKey('NetworkPortEthernet', $data['_networkports']);
        $this->assertNotEmpty($data['_networkports']['NetworkPortEthernet']);
        $networkport = $data['_networkports']['NetworkPortEthernet'][0];
        $this->assertArrayHasKey('NetworkName', $networkport);
        $this->assertArrayHasKey('networkports_id_opposite', $networkport);
        $this->assertArrayHasKey('netport_id', $networkport);
        $this->assertEquals($networkports[1], $networkport['networkports_id_opposite']);
    }

    public function testGetItemsCommonDBChild()
    {
        // test the case have DBChild not have entities_id
        $ticketTemplate = new TicketTemplate();
        $ticketTMF = new TicketTemplateMandatoryField();

        $tt_id = $ticketTemplate->add([
            'entities_id' => getItemByTypeName('Entity', '_test_child_1', true),
            'name'        => 'test',
        ]);
        $this->assertTrue((bool) $tt_id);

        $ttmf_id = $ticketTMF->add([
            'tickettemplates_id' => $tt_id,
            'num'                => 7,
        ]);
        $this->assertTrue((bool) $ttmf_id);

        $data = $this->query(
            'getItems',
            [
                'query'     => [
                    'searchText' => ['tickettemplates_id' => "^" . $tt_id . "$"],
                ],
                'itemtype'   => 'TicketTemplateMandatoryField',
                'headers'    => ['Session-Token' => $this->session_token],
            ],
            200
        );
        if (isset($data['headers'])) {
            unset($data['headers']);
        }
        $this->assertCount(1, $data);
    }

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
        $this->assertTrue($user->getFromDB($id));

        // Set a pic URL
        $success = $user->update([
            'id'      => $id,
            '_picture' => [$pic],
        ]);
        $this->assertTrue($success);

        // Get updated pic url
        $pic = $user->fields['picture'];
        $this->assertNotEmpty($pic);

        // Check pic was moved correctly into _picture folder
        $this->assertTrue(file_exists(GLPI_PICTURE_DIR . "/$pic"));
        $file_content = file_get_contents(GLPI_PICTURE_DIR . "/$pic");
        $this->assertNotEmpty($file_content);

        // Request
        $response = $this->query("User/$id/Picture", $params, 200, '', true);
        $this->assertEquals($file_content, $response->__toString(), sprintf("File %s doesn't match", GLPI_PICTURE_DIR . "/$pic"));

        /**
         * Case 2: user doesn't exist
         */

        // Request
        $response = $this->query("User/99999999/Picture", $params, 400, "ERROR");
        $this->assertCount(2, $response);
        $this->assertStringContainsString("Bad request: user with id '99999999' not found", $response[1]);

        /**
         * Case 3: user with no pictures
         */

        // Remove pic URL
        $success = $user->update([
            'id'             => $id,
            '_blank_picture' => true,
        ]);
        $this->assertTrue($success);

        // Request
        $response = $this->query("User/$id/Picture", $params, 204);
        $this->assertNull($response);
    }

    public static function deprecatedProvider(): array
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
     * @param class-string $provider
     */
    #[DataProvider('deprecatedProvider')]
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
        $this->assertGreaterThan(0, $item_id);

        // Call API
        $data = $this->query(
            "$deprecated_itemtype/$item_id",
            ['headers' => $headers],
            200
        );
        $this->assertIsArray($data);
        $this->assertCount(count($deprecated_fields) + 1, $data); // + 1 for headers
        foreach ($deprecated_fields as $field) {
            $this->assertArrayHasKey($field, $data);
        }

        // Clean db to prevent unicity failure on next run
        $item->delete(['id' => $item_id], true);
    }

    /**
     * @param class-string $provider
     */
    #[DataProvider('deprecatedProvider')]
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
        $this->assertGreaterThan(0, $item_id);

        // Call API
        $data = $this->query(
            "$deprecated_itemtype",
            ['headers' => $headers],
            [200, 206]
        );
        $this->assertIsArray($data);
        unset($data["headers"]);

        foreach ($data as $row) {
            $this->assertIsArray($row);
            $this->assertCount(count($deprecated_fields), $row);
            foreach ($deprecated_fields as $field) {
                $this->assertArrayHasKey($field, $row);
            }
        }

        // Clean db to prevent unicity failure on next run
        $item->delete(['id' => $item_id], true);
    }

    /**
     * @param class-string $provider
     */
    #[DataProvider('deprecatedProvider')]
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
            'json'    => ['input' => $input],
        ], 201);

        $this->assertGreaterThan(0, $data['id']);
        $this->assertTrue($item->getFromDB($data['id']));

        foreach ($expected_after_insert as $field => $value) {
            $this->assertEquals($value, $item->fields[$field]);
        }

        // Clean db to prevent unicity failure on next run
        $item->delete(['id' => $data['id']], true);
    }

    /**
     * @param class-string $provider
     */
    #[DataProvider('deprecatedProvider')]
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
        $this->assertGreaterThan(0, $item_id);

        // Call API
        $this->query(
            "$deprecated_itemtype/$item_id",
            [
                'headers' => $headers,
                'verb'    => "PUT",
                'json'    => ['input' => $update_input],
            ],
            200
        );

        // Check expected values
        $this->assertTrue($item->getFromDB($item_id));

        foreach ($expected_after_update as $field => $value) {
            $this->assertEquals($value, $item->fields[$field]);
        }

        // Clean db to prevent unicity failure on next run
        $item->delete(['id' => $item_id], true);
    }

    /**
     * @param class-string $provider
     */
    #[DataProvider('deprecatedProvider')]
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
        $this->assertGreaterThan(0, $item_id);

        // Call API
        $this->query(
            "$deprecated_itemtype/$item_id?force_purge=1",
            [
                'headers' => $headers,
                'verb'    => "DELETE",
            ],
            200,
            "",
            true
        );

        $this->assertFalse($item->getFromDB($item_id));
    }

    /**
     * @param class-string $provider
     */
    #[DataProvider('deprecatedProvider')]
    public function testDeprecatedListSearchOptions(string $provider)
    {
        // Get params from provider
        $deprecated_itemtype   = $provider::getDeprecatedType();

        $headers = ['Session-Token' => $this->session_token];

        $data = $this->query(
            "listSearchOptions/$deprecated_itemtype/",
            ['headers' => $headers]
        );

        $expected = file_get_contents(
            __DIR__ . "/../deprecated-searchoptions/$deprecated_itemtype.json"
        );
        $this->assertNotEmpty($expected);

        unset($data['headers']);
        $this->assertEquals(json_decode($expected, true), $data);
    }

    /**
     * @param class-string $provider
     */
    #[DataProvider('deprecatedProvider')]
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
        $this->assertEquals(
            $data['rawdata']['sql']['search'],
            $deprecated_data['rawdata']['sql']['search']
        );
    }


    protected function testGetMassiveActionsProvider(): array
    {
        // Create a computer with "is_deleted = 1" for our tests
        $computer = new Computer();
        $deleted_computers_id = $computer->add([
            'name' => 'test deleted PC',
            'entities_id' => getItemByTypeName("Entity", '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, $deleted_computers_id);
        $this->assertTrue($computer->delete(['id' => $deleted_computers_id]));
        $this->assertTrue($computer->getFromDB($deleted_computers_id));
        $this->assertEquals(1, $computer->fields['is_deleted']);

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
                    ["key" => "Item_Line:add",                   "label" => "Add a phone line"],
                    ["key" => "Item_Line:remove",                "label" => "Remove a phone line"],
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
                    ["key" => "MassiveAction:create_template",   "label" => "Create template"],
                    ["key" => "Item_Line:add",                   "label" => "Add a phone line"],
                    ["key" => "Item_Line:remove",                "label" => "Remove a phone line"],
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
     */
    public function testGetMassiveActions(): void
    {
        foreach ($this->testGetMassiveActionsProvider() as $row) {
            $url    = $row['url'];
            $status  = $row['status'];
            $response = $row['response'] ?? null;
            $error   = $row['error'] ?? '';

            $headers = ['Session-Token' => $this->session_token];
            $data    = $this->query(
                $url,
                ['headers' => $headers],
                $status,
                $error
            );

            // If no errors are expected, check results
            if (empty($error)) {
                unset($data['headers']);
                $this->assertEquals($response, $data);
            }
        }
    }

    public static function getMassiveActionParametersProvider(): array
    {
        return [
            [
                'url' => 'getMassiveActionParameters/Computer',
                'status' => 400,
                'response' => [],
                'error' => "ERROR_MASSIVEACTION_KEY",
            ],
            [
                'url' => 'getMassiveActionParameters/Computer/MassiveAction:doesnotexist',
                'status' => 400,
                'response' => [],
                'error' => "ERROR_MASSIVEACTION_KEY",
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
                'url' => 'getMassiveActionParameters/Computer/MassiveAction:create_template',
                'status' => 400,
                'response' => [],
                'error' => "ERROR_MASSIVEACTION_KEY",
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
     */
    #[DataProvider('getMassiveActionParametersProvider')]
    public function testGetMassiveActionParameters(
        string $url,
        int $status,
        ?array $response,
        string $error = ""
    ): void {
        $headers = ['Session-Token' => $this->session_token];
        $data    = $this->query(
            $url,
            ['headers' => $headers],
            $status,
            $error
        );

        // If no errors are expected, check results
        if (empty($error)) {
            unset($data['headers']);
            $this->assertEquals($response, $data);
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
                'error' => "ERROR_MASSIVEACTION_KEY",
            ],
            [
                'url' => 'applyMassiveAction/Computer/MassiveAction:doesnotexist',
                'payload' => [
                    'ids' => [getItemByTypeName('Computer', '_test_pc01', true)],
                ],
                'status' => 400,
                'response' => [],
                'error' => "ERROR_MASSIVEACTION_KEY",
            ],
            [
                'url' => 'applyMassiveAction/Computer/MassiveAction:amend_comment',
                'payload' => [
                    'ids' => [],
                ],
                'status' => 400,
                'response' => [],
                'error' => "ERROR_MASSIVEACTION_NO_IDS",
            ],
            [
                'url' => 'applyMassiveAction/Computer/MassiveAction:amend_comment',
                'payload' => [
                    'ids' => [
                        getItemByTypeName('Computer', '_test_pc01', true),
                        getItemByTypeName('Computer', '_test_pc02', true),
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
                        $this->assertTrue($update);
                        $this->assertEquals("test comment", $computer->fields['comment']);
                    }
                },
                'after_test' => function () {
                    $computers = ['_test_pc01', '_test_pc02'];
                    foreach ($computers as $computer) {
                        // Check that "comment" field was modified as expected
                        $computer = getItemByTypeName('Computer', $computer);
                        $this->assertEquals("test comment\n\nnewtexttoadd", $computer->fields['comment']);
                    }
                },
            ],
            [
                'url' => 'applyMassiveAction/Computer/MassiveAction:add_note',
                'payload' => [
                    'ids' => [
                        getItemByTypeName('Computer', '_test_pc01', true),
                        getItemByTypeName('Computer', '_test_pc02', true),
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
                            $this->assertTrue($deletion);
                        }

                        // Check that the items have no notes remaining
                        $this->assertCount(
                            0,
                            $note->find([
                                'itemtype' => 'Computer',
                                'items_id' => getItemByTypeName('Computer', $computer, true),
                            ])
                        );
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
                        $this->assertCount(1, $existing_notes);

                        foreach ($existing_notes as $existing_note) {
                            $this->assertEquals("new note", $existing_note['content']);
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Tests for the "applyMassiveAction" endpoint
     */
    public function testApplyMassiveAction(): void
    {
        foreach ($this->testApplyMassiveActionProvider() as $row) {
            $url = $row['url'];
            $payload = $row['payload'];
            $status = $row['status'];
            $response = $row['response'] ?? null;
            $error = $row['error'] ?? '';
            $before_test = $row['before_test'] ?? null;
            $after_test = $row['after_test'] ?? null;

            if (!is_null($before_test)) {
                $before_test();
            }

            $headers = ['Session-Token' => $this->session_token];
            $data = $this->query(
                $url,
                [
                    'headers' => $headers,
                    'verb' => 'POST',
                    'json' => $payload,
                ],
                $status,
                $error
            );

            // If no errors are expected, check results
            if (empty($error)) {
                unset($data['headers']);
                $this->assertEquals($response, $data);
            }

            if (!is_null($after_test)) {
                $after_test();
            }
        }
    }

    /**
     * Data provider for testReturnSanitizedContentUnit
     *
     * @return array
     */
    public static function testReturnSanitizedContentUnitProvider(): array
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
            $expected_output,
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

        $data = $this->query(
            $url,
            [
                'headers' => $headers,
                'verb'    => $method,
            ],
            200
        );
        $this->assertEquals("<>", $data['comment']);
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
                'content' => 'content',
            ],
        ];
        $data = $this->query(
            "/Ticket",
            [
                'headers' => $headers,
                'verb'    => "POST",
                'json'    => $input,
            ],
            201
        );
        $this->assertGreaterThan(0, $data['id']);
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
                        ],
                    ],
                ],
            ],
        ];
        $this->query(
            "/Ticket/$tickets_id/",
            [
                'headers' => $headers,
                'verb'    => "PUT",
                'json'    => $input,
            ],
            200
        );

        // Check assigned groups
        $data = $this->query(
            "/Ticket/$tickets_id/Group_Ticket",
            [
                'headers' => $headers,
                'verb'    => "GET",
            ],
            200
        );

        $this->assertEquals($tickets_id, $data[0]['tickets_id']);
        $this->assertEquals($groups_id, $data[0]['groups_id']);
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
                            ],
                        ],
                        '',
                        '&'
                    ),
                ]
            );
        } catch (RequestException $e) {
            $response = $e->getResponse();
        }

        // Check response
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody()->getContents();
        $this->assertEquals(
            [
                [
                    (string) $computers_id => true,
                    'message'             => '',
                ],
            ],
            json_decode($body, true)
        );

        // Check computer is updated
        $computer = new Computer();
        $this->assertTrue((bool) $computer->getFromDB($computers_id));
        $this->assertSame('abcdefg', $computer->fields['serial']);
        $this->assertSame('This computer has been updated.', $computer->fields['comment']);
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
                                'id' => $computers_id,
                            ],
                        ]
                    ),
                ]
            );
        } catch (RequestException $e) {
            $response = $e->getResponse();
        }

        // Check response
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody()->getContents();
        $this->assertEquals(
            [
                [
                    (string) $computers_id => true,
                    'message'             => '',
                ],
            ],
            json_decode($body, true)
        );

        // Check computer is updated
        $computer = new Computer();
        $this->assertTrue((bool) $computer->getFromDB($computers_id));
        $this->assertTrue((bool) $computer->getField('is_deleted'));
    }

    public function testSearchTextResponseCode()
    {
        $data = $this->query(
            'getItems',
            [
                'itemtype' => Computer::class,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['searchText' => ['test' => 'test']],
            ],
            400,
            'ERROR_FIELD_NOT_FOUND'
        );

        $this->assertNotFalse($data);

        $data = $this->query(
            'getItems',
            ['itemtype' => Computer::class,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['searchText' => ['name' => 'test']],
            ],
            200,
        );

        $this->assertNotFalse($data);
    }

    public function testUndisclosedNotificationContent()
    {
        // Enable notifications
        Config::setConfigurationValues('core', [
            'use_notifications' => '1',
            'notifications_mailing' => '1',
        ]);

        // Trigger a notification sending
        $user = getItemByTypeName('User', TU_USER);
        $this->query(
            'lostPassword',
            [
                'verb'    => 'PATCH',
                'json'    => [
                    'email'  => $user->getDefaultEmail(),
                ],
            ],
            200
        );

        // need to be GLPI to access the root entity notifications
        $data = $this->query(
            'initSession',
            [
                'query' => [
                    'login'    => 'glpi',
                    'password' => 'glpi',
                ],
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
            fn($notification) => $notification['name'] === '[GLPI] Forgotten password?'
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
                    'forcedisplay'  => [12, 13],
                ],
            ],
            200
        );
        $this->assertIsArray($result);
        unset($result['headers']);

        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['data']);

        $notifications = \array_filter(
            $result['data'],
            fn($notification) => $notification['1'] === '[GLPI] Forgotten password?'
        );

        foreach ($notifications as $notification) {
            $this->assertEquals('********', $notification['12']); // 12 = body_html
            $this->assertEquals('********', $notification['13']); // 13 = body_text
        }
    }

    public function testGetItemIDZero()
    {
        $this->expectException(ClientException::class);
        $this->doHttpRequest(
            'GET',
            '/Entity/0',
            [
                'headers'  => ['Session-Token' => $this->session_token],
            ]
        );
        // We are connected to a child entity, so we expect a 403 error
        $this->expectExceptionMessage('403 Forbidden');

        $this->expectException(ClientException::class);
        $this->doHttpRequest(
            'GET',
            '/User/0',
            [
                'headers'  => ['Session-Token' => $this->session_token],
            ]
        );
        $this->expectExceptionMessage('404 Not Found');
    }


    /**
     * Test different input types for PUT/PATCH requests to ensure the fix for
     * "Attempt to assign property 'id' on array" error works correctly
     *
     * @covers API::updateItems
     */
    public function testUpdateItemsWithDifferentInputTypes()
    {
        $headers = ['Session-Token' => $this->session_token];

        // Use existing test entities instead of creating new ones
        $entity_id = getItemByTypeName('Entity', '_test_child_1', true);
        $entity_2_id = getItemByTypeName('Entity', '_test_child_2', true);
        $this->assertGreaterThan(0, $entity_id);
        $this->assertGreaterThan(0, $entity_2_id);

        // Test Case 1: Entity with object input and ID in URL (should work)
        $data = $this->query(
            "Entity/$entity_id",
            [
                'headers' => $headers,
                'verb'    => 'PUT',
                'json'    => [
                    'input' => [
                        'comment' => 'Updated via object input with ID in URL',
                    ],
                ],
            ],
            200
        );
        $this->assertIsArray($data);
        $this->assertTrue((bool) $data[0][$entity_id]);

        // Verify the update
        $entity_obj = new \Entity();
        $this->assertTrue($entity_obj->getFromDB($entity_id));
        $this->assertEquals('Updated via object input with ID in URL', $entity_obj->fields['comment']);

        // Test Case 2: Entity with indexed array input (should work without crashing)
        $data = $this->query(
            "Entity",
            [
                'headers' => $headers,
                'verb'    => 'PUT',
                'json'    => [
                    'input' => [
                        [
                            'id' => $entity_id,
                            'comment' => 'Updated via indexed array',
                        ],
                    ],
                ],
            ],
            200
        );
        $this->assertIsArray($data);
        $this->assertTrue((bool) $data[0][$entity_id]);

        // Verify the update
        $entity_obj = new \Entity();
        $this->assertTrue($entity_obj->getFromDB($entity_id));
        $this->assertEquals('Updated via indexed array', $entity_obj->fields['comment']);

        // Test Case 3: Entity with multiple items in indexed array (using both test entities)
        $data = $this->query(
            "Entity",
            [
                'headers' => $headers,
                'verb'    => 'PUT',
                'json'    => [
                    'input' => [
                        [
                            'id' => $entity_id,
                            'comment' => 'Multi-update entity 1',
                        ],
                        [
                            'id' => $entity_2_id,
                            'comment' => 'Multi-update entity 2',
                        ],
                    ],
                ],
            ],
            200
        );

        // Remove headers from response for easier assertions
        unset($data['headers']);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertTrue((bool) $data[0][$entity_id]);
        $this->assertTrue((bool) $data[1][$entity_2_id]);

        // Verify the updates
        $entity_obj = new \Entity();
        $this->assertTrue($entity_obj->getFromDB($entity_id));
        $this->assertEquals('Multi-update entity 1', $entity_obj->fields['comment']);

        $this->assertTrue($entity_obj->getFromDB($entity_2_id));
        $this->assertEquals('Multi-update entity 2', $entity_obj->fields['comment']);
    }

    public static function systemSQLCriteriaProvider()
    {
        yield [
            'type' => 'Glpi\\CustomAsset\\Test01Asset',
            'field' => 'name',
            'expected' => ['TestA', 'TestB'],
            'not_expected' => ['Test02 A', 'Test02 B'],
        ];
        yield [
            'type' => 'Glpi\\CustomAsset\\Test02Asset',
            'field' => 'name',
            'expected' => ['Test02 A', 'Test02 B'],
            'not_expected' => ['TestA', 'TestB'],
        ];

        yield [
            'type' => FormTranslation::class,
            'field' => 'itemtype',
            'expected' => [
                Form::class,
                Section::class,
                Question::class,
            ],
            'not_expected' => [
                GlpiPageTile::class,
            ],
        ];

        yield [
            'type' => HelpdeskTranslation::class,
            'field' => 'itemtype',
            'expected' => [
                GlpiPageTile::class,
            ],
            'not_expected' => [
                Form::class,
                Section::class,
                Question::class,
            ],
        ];
    }

    /**
     * Some itemtypes share the same DB table and use `getSystemSQLCriteria` to specify criteria needed to limit DB queries to related types only.
     * This test checks that the `getSystemSQLCriteria` method works correctly for these itemtypes in the legacy API.
     */
    #[DataProvider('systemSQLCriteriaProvider')]
    public function testSystemSQLCriteria(string $type, string $field, array $expected, array $not_expected = []): void
    {
        $headers = ['Session-Token' => $this->session_token];
        $data = json_decode(
            $this->query(
                resource: $type . '?range=0-9000',
                params: [
                    'headers' => $headers,
                ],
                no_decode: true
            )
        );
        $values = array_column($data, $field);
        foreach ($expected as $v) {
            $this->assertContains($v, $values);
        }
        foreach ($not_expected as $v) {
            $this->assertNotContains($v, $values);
        }
    }
}
