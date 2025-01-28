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

namespace tests\units\Glpi\Inventory;

use GuzzleHttp;
use Psr\Http\Client\RequestExceptionInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

class RequestTest extends \GLPITestCase
{
    private $http_client;
    private $base_uri;

    public function setUp(): void
    {
        global $CFG_GLPI;

        $this->http_client = new GuzzleHttp\Client();
        $this->base_uri    = trim($CFG_GLPI['url_base'], "/") . "/";

        parent::setUp();
    }

    /**
     * Check a XML response
     *
     * @param Response $res   Request response
     * @param string   $reply Reply tag contents
     * @param integer  $reply Reply HTTP code
     *
     * @return void
     */
    private function checkXmlResponse(GuzzleHttp\Psr7\Response $res, $reply, $code)
    {
        $this->assertSame($code, $res->getStatusCode());
        $this->assertSame(
            'application/xml',
            $res->getHeader('content-type')[0]
        );
        $this->assertSame(
            "<?xml version=\"1.0\"?>\n<REPLY>$reply</REPLY>",
            (string)$res->getBody()
        );
    }

    public function testUnsupportedHttpMethod()
    {
        try {
            $this->http_client->request(
                'GET',
                $this->base_uri . 'front/inventory.php'
            );
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $this->assertInstanceOf(Response::class, $response);
            $this->assertSame(405, $response->getStatusCode());
            $this->assertSame('', (string)$response->getBody());
        }
    }

    public function testUnsupportedLegacyRequest()
    {
        try {
            $this->http_client->request(
                'GET',
                $this->base_uri . 'front/inventory.php?action=getConfig'
            );
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $this->assertInstanceOf(Response::class, $response);
            $this->assertSame(400, $response->getStatusCode());
            $this->assertSame(
                '{"status":"error","message":"Protocol not supported","expiration":24}',
                (string)$response->getBody()
            );
        }
    }

    public function testRequestInvalidContent()
    {
        try {
            $this->http_client->request(
                'POST',
                $this->base_uri . 'front/inventory.php',
                [
                    'headers' => [
                        'Content-Type' => 'application/xml'
                    ]
                ]
            );
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $this->assertInstanceOf(Response::class, $response);
            $this->assertSame(400, $response->getStatusCode());
            $this->assertSame(
                "<?xml version=\"1.0\"?>\n<REPLY><ERROR><![CDATA[XML not well formed!]]></ERROR></REPLY>",
                (string)$response->getBody()
            );
        }
    }

    public function testRequestInvalidJSONContent()
    {
        try {
            $this->http_client->request(
                'POST',
                $this->base_uri . 'front/inventory.php',
                [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ]
                ]
            );
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $this->assertInstanceOf(Response::class, $response);
            $this->assertSame(400, $response->getStatusCode());
            $this->assertSame(
                '{"status":"error","message":"JSON not well formed!","expiration":24}',
                (string)$response->getBody()
            );
        }

        try {
            $this->http_client->request(
                'POST',
                $this->base_uri . 'front/inventory.php',
                [
                    'headers' => [
                        'GLPI-Agent-ID' => 'a31ff7b5-4d8d-4e39-891e-0cca91d9df13'
                    ],
                    'body'   => '{ bad content'
                ]
            );
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $this->assertInstanceOf(Response::class, $response);
            $this->assertSame(400, $response->getStatusCode());
            $this->assertSame(
                '{"status":"error","message":"JSON not well formed!","expiration":24}',
                (string)$response->getBody()
            );
        }

        try {
            $this->http_client->request(
                'POST',
                $this->base_uri . 'front/inventory.php',
                [
                    'headers' => [
                        'Content-Type' => 'application/x-compress-zlib',
                        'GLPI-Agent-ID' => 'a31ff7b5-4d8d-4e39-891e-0cca91d9df13'
                    ],
                    'body'   => gzcompress('{ bad content')
                ]
            );
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $this->assertInstanceOf(Response::class, $response);
            $this->assertSame(400, $response->getStatusCode());
            $this->assertSame(
                gzcompress('{"status":"error","message":"JSON not well formed!","expiration":24}'),
                (string)$response->getBody()
            );
        }
    }

    public function testPrologRequest()
    {
        $res = $this->http_client->request(
            'POST',
            $this->base_uri . 'front/inventory.php',
            [
                'headers' => [
                    'Content-Type' => 'application/xml'
                ],
                'body'   => '<?xml version="1.0" encoding="UTF-8" ?>' .
                '<REQUEST>' .
                  '<DEVICEID>mydeviceuniqueid</DEVICEID>' .
                  '<QUERY>PROLOG</QUERY>' .
                '</REQUEST>'
            ]
        );
        $this->checkXmlResponse($res, '<PROLOG_FREQ>24</PROLOG_FREQ><RESPONSE>SEND</RESPONSE>', 200);
    }
}
