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
class APIRest extends APIBaseClass
{
    protected function getLogFilePath(): string
    {
        return GLPI_LOG_DIR . "/php-errors.log";
    }

    public function beforeTestMethod($method)
    {
        global $CFG_GLPI;

        // Empty log file
        $file_updated = file_put_contents($this->getLogFilePath(), "");
        $this->variable($file_updated)->isNotIdenticalTo(false);

        $this->http_client = new GuzzleHttp\Client();
        $this->base_uri    = trim($CFG_GLPI['url_base_api'], "/") . "/";

        parent::beforeTestMethod($method);
    }

    public function afterTestMethod($method)
    {
        // Check that no errors occurred on the test server
        $this->string(file_get_contents($this->getLogFilePath()))->isEmpty();
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
            ['headers' => [
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
            ['headers' => [
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
        parent::badEndpoint(400, 'ERROR_RESOURCE_NOT_FOUND_NOR_COMMONDBTM');

        $data = $this->query(
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

        $this->array($data)
         ->hasKey('id')
         ->hasKey('message');
        $documents_id = $data['id'];
        $this->boolean(is_numeric($documents_id))->isTrue();
        $this->integer((int)$documents_id)->isGreaterThan(0);

        $document        = new \Document();
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
    public function testUpdateItem()
    {
       //parent::testUpdateItem($session_token, $computers_id);

       //try to update an item without input
        $data = $this->query(
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
        $this->boolean((bool)$tt_id)->isTrue();

        $ttmf_id = $ticketTMF->add([
            'tickettemplates_id' => $tt_id,
            'num'                => 7
        ]);
        $this->boolean((bool)$ttmf_id)->isTrue();

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
        $json_data = json_encode($data, JSON_PRETTY_PRINT);
        $this->string($json_data)->isEqualTo($expected);
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
                    ["key" => "Infocom:activate",                "label" => "Enable the financial and administrative information"],
                    ["key" => "MassiveAction:delete",            "label" => "Put in trashbin"],
                    ["key" => "ObjectLock:unlock",               "label" => "Unlock items"],
                    ["key" => "MassiveAction:add_transfer_list", "label" => "Add to transfer list"],
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
                    ["key" => "MassiveAction:add_transfer_list", "label" => "Add to transfer list"],
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
                'url' => 'getMassiveActionParameters/Computer/MassiveAction:add_transfer_list',
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
     * Unit test for the "returnSanitizedContent" method
     *
     * @dataProvider testReturnSanitizedContentUnitProvider
     *
     * @param string $header_value    Header value to be tested
     * @param bool   $expected_output Expected output for this header
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
        $this->boolean(
            $api->returnSanitizedContent()
        )->isEqualTo($expected_output);

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
        $data = $this->query($url, [
            'headers' => $headers,
            'verb'    => $method,
        ], 200);
        $this->string($data['comment'])->isEqualTo("&#60;&#62;");

        // Add additional header
        $headers['X-GLPI-Sanitized-Content'] = "false";

        // Execute second test (expect decoded content)
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
}
