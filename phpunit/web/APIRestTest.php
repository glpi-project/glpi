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

namespace tests\units\Glpi\Api;

use APIBaseClass;
use Computer;
use Glpi\Tests\Api\Deprecated\Computer_SoftwareLicense;
use Glpi\Tests\Api\Deprecated\Computer_SoftwareVersion;
use Glpi\Tests\Api\Deprecated\TicketFollowup;
use GuzzleHttp;
use Notepad;

/* Test for inc/api/api.class.php */

/**
 * @engine isolate
 */
class APIRestTest extends APIBaseClass
{
    protected function getLogFilePath(): string
    {
        return GLPI_LOG_DIR . "/php-errors.log";
    }

    public function setUp(): void
    {
        global $CFG_GLPI;

        // Empty log file
        $file_updated = file_put_contents($this->getLogFilePath(), "");
        $this->assertNotSame(false, $file_updated);

        $this->http_client = new GuzzleHttp\Client();
        $this->base_uri    = trim($CFG_GLPI['url_base_api'], "/") . "/";

        parent::setUp();
    }

    public function tearDown(): void
    {
        // Check that no errors occurred on the test server
        $this->assertEmpty(file_get_contents($this->getLogFilePath()));
        parent::tearDown();
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

        $verb         = isset($params['verb'])
                        ? $params['verb']
                        : 'GET';

        $resource_path  = parse_url($resource, PHP_URL_PATH);
        $resource_query = parse_url($resource, PHP_URL_QUERY);

        $relative_uri = (!in_array($resource_path, ['getItem', 'getItems', 'createItems',
            'updateItems', 'deleteItems'
        ])
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

    /**
     * @tags   api
     * @covers API::cors
     **/
    public function testCORS()
    {
        $res = $this->doHttpRequest(
            'OPTIONS',
            '',
            ['headers' => [
                'Origin' => "http://localhost",
                'Access-Control-Request-Method'  => 'GET',
                'Access-Control-Request-Headers' => 'X-Requested-With'
            ]
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

    /**
     * @tags   api
     * @covers API::inlineDocumentation
     **/
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

    /**
     * @tags   api
     * @covers API::initSession
     **/
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
        $this->assertTrue($updated);

        $res = $this->doHttpRequest(
            'GET',
            'initSession?get_full_session=true',
            [
                'headers' => [
                    'Authorization' => "user_token $token"
                ]
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

    /**
     * @tags    api
     */
    public function testBadEndpoint()
    {
        parent::badEndpoint(400, 'ERROR_RESOURCE_NOT_FOUND_NOR_COMMONDBTM');

        $this->query(
            'getItems',
            ['itemtype'        => 'badEndpoint',
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
            ['itemtype' => 'Computer',
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

        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        unset($data['headers']);

        $computer = array_shift($data);
        $this->assertIsArray($computer);
        $this->assertArrayHasKey($computers_id, $computer);
        $this->assertArrayHasKey('message', $computer);
        $this->assertTrue((bool)$computer[$computers_id]);

        $computer = new \Computer();
        $this->assertTrue((bool)$computer->getFromDB($computers_id));
        $this->assertSame('abcdefg', $computer->fields['serial']);
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
                ]
            ],
            201
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('message', $data);
        $documents_id = $data['id'];
        $this->assertTrue(is_numeric($documents_id));
        $this->assertGreaterThan(0, (int)$documents_id);

        $document        = new \Document();
        $this->assertTrue((bool)$document->getFromDB($documents_id));

        $this->assertIsArray($document->fields);
        $this->assertSame('text/plain', $document->fields['mime']);
        $this->assertSame($document_name, $document->fields['name']);
        $this->assertSame($filename, $document->fields['filename']);

        $this->assertStringContainsString('MD/', $document->fields['filepath']);
    }

    /**
     * @tags    api
     * @covers  API::updateItems
     */
    public function testUpdateItem()
    {
        //parent::testUpdateItem($session_token, $computers_id);

        //try to update an item without input
        $this->query(
            'updateItems',
            ['itemtype' => 'Computer',
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
        $ticketTemplate = new \TicketTemplate();
        $ticketTMF = new \TicketTemplateMandatoryField();

        $tt_id = $ticketTemplate->add([
            'entities_id' => getItemByTypeName('Entity', '_test_child_1', true),
            'name'        => 'test'
        ]);
        $this->assertTrue((bool)$tt_id);

        $ttmf_id = $ticketTMF->add([
            'tickettemplates_id' => $tt_id,
            'num'                => 7
        ]);
        $this->assertTrue((bool)$ttmf_id);

        $data = $this->query(
            'getItems',
            ['query'     => [
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
        $this->assertCount(1, $data);
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
        copy("phpunit/$pic", GLPI_TMP_DIR . "/$pic");

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
        ];
    }

    /**
     * @dataProvider deprecatedProvider
     *
     * @param class-string $provider
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
     * @dataProvider deprecatedProvider
     *
     * @param class-string $provider
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
     * @dataProvider deprecatedProvider
     *
     * @param class-string $provider
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

        $this->assertGreaterThan(0, $data['id']);
        $this->assertTrue($item->getFromDB($data['id']));

        foreach ($expected_after_insert as $field => $value) {
            $this->assertEquals($value, $item->fields[$field]);
        }

        // Clean db to prevent unicity failure on next run
        $item->delete(['id' => $data['id']], true);
    }

    /**
     * @dataProvider deprecatedProvider
     *
     * @param class-string $provider
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
        $this->assertGreaterThan(0, $item_id);

        // Call API
        $this->query(
            "$deprecated_itemtype/$item_id",
            [
                'headers' => $headers,
                'verb'    => "PUT",
                'json'    => ['input' => $update_input]
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
     * @dataProvider deprecatedProvider
     *
     * @param class-string $provider
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
     * @dataProvider deprecatedProvider
     *
     * @param class-string $provider
     */
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
        $json_data = json_encode($data, JSON_PRETTY_PRINT);
        $this->assertEquals($expected, $json_data);
    }

    /**
     * @dataProvider deprecatedProvider
     *
     * @param class-string $provider
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
            'entities_id' => getItemByTypeName("Entity", '_test_root_entity', true)
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
                    ["key" => "Infocom:activate",                "label" => "Enable the financial and administrative information"],
                    ["key" => "MassiveAction:delete",            "label" => "Put in trashbin"],
                    ["key" => "ObjectLock:unlock",               "label" => "Unlock items"],
                    ["key" => "Appliance:add_item",              "label" => "Associate to an appliance"],
                    ["key" => "Item_Rack:delete",                "label" => "Remove from a rack"],
                    ["key" => "Item_OperatingSystem:update",     "label" => "Operating systems"],
                    ["key" => "Computer_Item:add",               "label" => "Connect"],
                    ["key" => "Item_SoftwareVersion:add",        "label" => "Install"],
                    ["key" => "Item_SoftwareLicense:add",        "label" => "Add a license"],
                    ["key" => "KnowbaseItem_Item:add",           "label" => "Link knowledgebase article"],
                    ["key" => "Document_Item:add",               "label" => "Add a document"],
                    ["key" => "Document_Item:remove",            "label" => "Remove a document"],
                    ["key" => "Contract_Item:add",               "label" => "Add a contract"],
                    ["key" => "Contract_Item:remove",            "label" => "Remove a contract"],
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
                    ["key" => "Infocom:activate",                "label" => "Enable the financial and administrative information"],
                    ["key" => "MassiveAction:delete",            "label" => "Put in trashbin"],
                    ["key" => "ObjectLock:unlock",               "label" => "Unlock items"],
                    ["key" => "Appliance:add_item",              "label" => "Associate to an appliance"],
                    ["key" => "Item_Rack:delete",                "label" => "Remove from a rack"],
                    ["key" => "Item_OperatingSystem:update",     "label" => "Operating systems"],
                    ["key" => "Computer_Item:add",               "label" => "Connect"],
                    ["key" => "Item_SoftwareVersion:add",        "label" => "Install"],
                    ["key" => "Item_SoftwareLicense:add",        "label" => "Add a license"],
                    ["key" => "KnowbaseItem_Item:add",           "label" => "Link knowledgebase article"],
                    ["key" => "Document_Item:add",               "label" => "Add a document"],
                    ["key" => "Document_Item:remove",            "label" => "Remove a document"],
                    ["key" => "Contract_Item:add",               "label" => "Add a contract"],
                    ["key" => "Contract_Item:remove",            "label" => "Remove a contract"],
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

    public static function testGetMassiveActionParametersProvider(): array
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
                'url' => 'getMassiveActionParameters/Computer/Computer_Item:add',
                'status' => 200,
                'response' => [
                    ["name" => "peer_computers_id", "type" => "dropdown"],
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
                }
            ]
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
     * Unit test for the "returnSanitizedContent" method
     *
     * @dataProvider testReturnSanitizedContentUnitProvider
     *
     * @param ?string $header_value    Header value to be tested
     * @param bool    $expected_output Expected output for this header
     *
     * @return void
     */
    public function testReturnSanitizedContentUnit(
        ?string $header_value,
        bool $expected_output
    ): void {
        $api = new \Glpi\Api\APIRest();

        if ($header_value === null) {
            // Simulate missing header
            unset($_SERVER['HTTP_X_GLPI_SANITIZED_CONTENT']);
        } else {
            $_SERVER['HTTP_X_GLPI_SANITIZED_CONTENT'] = $header_value;
        }

        // Run test
        $this->assertEquals(
            $expected_output,
            $api->returnSanitizedContent()
        );

        // Clean header
        unset($_SERVER['HTTP_X_GLPI_SANITIZED_CONTENT']);
    }

    /**
     * Functional test for the "returnSanitizedContent" method
     *
     * @return void
     */
    public function testReturnSanitizedContentFunctional(): void
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

        // Execute first test (keep encoded content)
        $data = $this->query(
            $url,
            [
                'headers' => $headers,
                'verb'    => $method,
            ],
            200
        );
        $this->assertEquals("&#60;&#62;", $data['comment']);

        // Add additional header
        $headers['X-GLPI-Sanitized-Content'] = "false";

        // Execute second test (expect decoded content)
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
                'content' => 'content'
            ]
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
                        ]
                    ]
                ]
            ]
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
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody()->getContents();
        $this->assertEquals(
            [
                [
                    (string)$computers_id => true,
                    'message'             => '',
                ]
            ],
            json_decode($body, true)
        );

        // Check computer is updated
        $computer = new \Computer();
        $this->assertTrue((bool)$computer->getFromDB($computers_id));
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
        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $body = $response->getBody()->getContents();
        $this->assertEquals(
            [
                [
                    (string)$computers_id => true,
                    'message'             => '',
                ]
            ],
            json_decode($body, true)
        );

        // Check computer is updated
        $computer = new \Computer();
        $this->assertTrue((bool)$computer->getFromDB($computers_id));
        $this->assertTrue((bool)$computer->getField('is_deleted'));
    }

    public function testSearchTextResponseCode()
    {
        $data = $this->query(
            'getItems',
            [
                'itemtype' => Computer::class,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['searchText' => ['test' => 'test']]
            ],
            400,
            'ERROR_FIELD_NOT_FOUND'
        );

        $this->assertNotFalse($data);

        $data = $this->query(
            'getItems',
            ['itemtype' => Computer::class,
                'headers'  => ['Session-Token' => $this->session_token],
                'query'    => ['searchText' => ['name' => 'test']]
            ],
            200,
        );

        $this->assertNotFalse($data);
    }
}
