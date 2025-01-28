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
use GuzzleHttp;

/* Test for inc/api/apixmlrpc.class.php */

/**
 * @engine isolate
 * @extensions xmlrpc
 */
class APIXmlrpcTest extends APIBaseClass
{
    public function setUp(): void
    {
        global $CFG_GLPI;

        $this->http_client = new GuzzleHttp\Client();
        $this->base_uri    = trim($CFG_GLPI['url_base'], "/") . "/apixmlrpc.php";

        parent::setUp();
    }

    protected function doHttpRequest($resource = "", $params = [])
    {
        $headers = ["Content-Type" => "text/xml"];
        $request = xmlrpc_encode_request($resource, $params);
        return $this->http_client->post(
            $this->base_uri,
            [
                'body'    => $request,
                'headers' => $headers
            ]
        );
    }

    protected function query($resource = "", $params = [], $expected_codes = 200, $expected_symbol = '')
    {
        //reconstruct params for xmlrpc (base params done for rest)
        $flat_params = array_merge(
            $params,
            isset($params['query'])   ? $params['query']   : [],
            isset($params['headers']) ? $params['headers'] : [],
            isset($params['json'])    ? $params['json']    : []
        );
        unset(
            $flat_params['query'],
            $flat_params['json'],
            $flat_params['verb']
        );
        if (isset($flat_params['Session-Token'])) {
            $flat_params['session_token'] = $flat_params['Session-Token'];
        }
        // launch query
        try {
            $res = $this->doHttpRequest($resource, $flat_params);
        } catch (\Throwable $e) {
            $response = $e->getResponse();
            $this->assertEquals($expected_codes, $response->getStatusCode());
            $body = xmlrpc_decode($response->getBody());
            $this->assertIsArray($body);
            $this->assertArrayHasKey('0', $body);
            $this->assertSame($expected_symbol, $body[0]);
            return;
        }
        // common tests
        if (isset($this->last_error)) {
            $this->assertNotNull($res);
        }
        $this->assertEquals(
            $expected_codes,
            $res->getStatusCode()
        );
        // retrieve data
        $data = xmlrpc_decode($res->getBody());
        if (is_array($data)) {
            $data['headers'] = $res->getHeaders();
        }
        return $data;
    }

    /**
     * @tags   api
     * @covers API::initSession
     */
    public function initSessionCredentials()
    {
        $data = $this->query(
            'initSession',
            ['query' => [
                'login'    => TU_USER,
                'password' => TU_PASS
            ]
            ]
        );

        $this->assertNotFalse($data);
        $this->assertArrayHasKey('session_token', $data);
        $this->session_token = $data['session_token'];
    }

    /**
     * @tags    api
     */
    public function testBadEndpoint()
    {
        parent::badEndpoint(405, 'ERROR_METHOD_NOT_ALLOWED');
    }

    /**
     * @tags    api
     * @covers  API::updateItems
     */
    public function testUpdateItem()
    {
       //:parent::testUpdateItem($session_token, $computers_id);

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
            'ERROR_BAD_ARRAY'
        );
    }
}
