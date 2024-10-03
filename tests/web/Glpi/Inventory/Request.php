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

namespace tests\units\Glpi\Inventory;

use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

class Request extends \DBTestCase
{
    private $http_client;
    private $base_uri;

    public function beforeTestMethod($method)
    {
        global $CFG_GLPI;

        $this->http_client = new GuzzleHttp\Client();
        $this->base_uri    = trim($CFG_GLPI['url_base'], "/") . "/";

        parent::beforeTestMethod($method);
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
        $this->integer($res->getStatusCode())->isIdenticalTo($code);
        $this->string($res->getHeader('content-type')[0])->isIdenticalTo('application/xml');
        $this->string((string)$res->getBody())
         ->isIdenticalTo("<?xml version=\"1.0\"?>\n<REPLY>$reply</REPLY>");
    }

    /**
     * Check a JSON response
     *
     * @param Response $res   Request response
     * @param string   $reply Reply tag contents
     * @param integer  $reply Reply HTTP code
     *
     * @return void
     */
    private function checkJsonResponse(GuzzleHttp\Psr7\Response $res, $reply, $code)
    {
        $this->integer($res->getStatusCode())->isIdenticalTo($code);
        $this->string($res->getHeader('content-type')[0])->isIdenticalTo('application/json');
        $this->string((string)$res->getBody())
         ->isIdenticalTo($reply);
    }

    public function testUnsupportedHttpMethod()
    {
        $this->exception(
            function () {
                $this->http_client->request(
                    'GET',
                    $this->base_uri . 'front/inventory.php'
                );
            }
        );
        $this->object($this->exception)->isInstanceOf(RequestException::class);
        $this->object($response = $this->exception->getResponse())->isInstanceOf(Response::class);
        $this->integer($response->getStatusCode())->isEqualTo(405);
        $this->string((string)$response->getBody())->isEqualTo('');
    }

    public function testUnsupportedLegacyRequest()
    {
        $this->exception(
            function () {
                $this->http_client->request(
                    'GET',
                    $this->base_uri . 'front/inventory.php?action=getConfig'
                );
            }
        );
        $this->object($this->exception)->isInstanceOf(RequestException::class);
        $this->object($response = $this->exception->getResponse())->isInstanceOf(Response::class);
        $this->integer($response->getStatusCode())->isEqualTo(400);
        $this->string((string)$response->getBody())->isEqualTo('{"status":"error","message":"Protocol not supported","expiration":24}');
    }

    public function testRequestInvalidContent()
    {
        $this->exception(
            function () {
                $this->http_client->request(
                    'POST',
                    $this->base_uri . 'front/inventory.php',
                    [
                        'headers' => [
                            'Content-Type' => 'application/xml'
                        ]
                    ]
                );
            }
        );
        $this->object($this->exception)->isInstanceOf(RequestException::class);
        $this->object($response = $this->exception->getResponse())->isInstanceOf(Response::class);
        $this->integer($response->getStatusCode())->isEqualTo(400);
        $this->string((string)$response->getBody())->isEqualTo(<<<XML
<?xml version="1.0"?>
<REPLY><ERROR><![CDATA[XML not well formed!]]></ERROR></REPLY>
XML
        );
    }

    public function testRequestInvalidJSONContent()
    {
        $this->exception(
            function () {
                $this->http_client->request(
                    'POST',
                    $this->base_uri . 'front/inventory.php',
                    [
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ]
                    ]
                );
            }
        );
        $this->object($this->exception)->isInstanceOf(RequestException::class);
        $this->object($response = $this->exception->getResponse())->isInstanceOf(Response::class);
        $this->integer($response->getStatusCode())->isEqualTo(400);
        $this->string((string)$response->getBody())->isEqualTo('{"status":"error","message":"JSON not well formed!","expiration":24}');

        $this->exception(
            function () {
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
            }
        );
        $this->object($this->exception)->isInstanceOf(RequestException::class);
        $this->object($response = $this->exception->getResponse())->isInstanceOf(Response::class);
        $this->integer($response->getStatusCode())->isEqualTo(400);
        $this->string((string)$response->getBody())->isEqualTo('{"status":"error","message":"JSON not well formed!","expiration":24}');

        $this->exception(
            function () {
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
            }
        );
        $this->object($this->exception)->isInstanceOf(RequestException::class);
        $this->object($response = $this->exception->getResponse())->isInstanceOf(Response::class);
        $this->integer($response->getStatusCode())->isEqualTo(400);
        $this->string((string)$response->getBody())->isEqualTo(gzcompress('{"status":"error","message":"JSON not well formed!","expiration":24}'));
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

    public function testAuthBasic()
    {
        /** @var mixed $DB */
        global $DB;

        $basic_auth_password = "a_password";
        $basic_auth_login = "a_login";
        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->boolean($conf->saveConf(
            [
                "enabled_inventory" => true,
                'auth_required' => \Glpi\Inventory\Conf::BASIC_AUTH,
                'basic_auth_login' => $basic_auth_login,
                'basic_auth_password' => $basic_auth_password
            ]
        ))->isTrue();
        $DB->commit();
        $this->logout();
        //first call should be unauthorized and return 401
        $this->exception(
            function () {
                $this->http_client->request(
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
            }
        );
        $this->object($this->exception)->isInstanceOf(RequestException::class);
        $this->object($response = $this->exception->getResponse())->isInstanceOf(Response::class);
        $this->checkJsonResponse($response, '{"status":"error","message":"Authorization header required to send an inventory","expiration":24}', 401);

        //second attempt should be authorized
        $res = $this->http_client->request(
            'POST',
            $this->base_uri . 'front/inventory.php',
            [
                'headers' => [
                    'Content-Type' => 'application/xml',
                    'Authorization' => 'Basic ' . base64_encode($basic_auth_login . ":" . $basic_auth_password)
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

    public function testAuthBasicMalformed()
    {
        /** @var mixed $DB */
        global $DB;

        $basic_auth_password = "a_password";
        $basic_auth_login = "a_login";

        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->boolean($conf->saveConf(
            [
                "enabled_inventory" => true,
                'auth_required' => \Glpi\Inventory\Conf::BASIC_AUTH,
                'basic_auth_login' => $basic_auth_login,
                'basic_auth_password' => $basic_auth_password
            ]
        ))->isTrue();
        $DB->commit();
        $this->logout();

        //first call should be unauthorized and return 401
        $this->exception(
            function () {
                $this->http_client->request(
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
            }
        );
        $this->object($this->exception)->isInstanceOf(RequestException::class);
        $this->object($response = $this->exception->getResponse())->isInstanceOf(Response::class);
        $this->checkJsonResponse($response, '{"status":"error","message":"Authorization header required to send an inventory","expiration":24}', 401);

        //second attempt should be unauthorized and return 401
        $this->exception(
            function () use ($basic_auth_login, $basic_auth_password) {
                $this->http_client->request(
                    'POST',
                    $this->base_uri . 'front/inventory.php',
                    [
                        'headers' => [
                            'Content-Type' => 'application/xml',
                            //deliberate omission of "Basic "
                            'Authorization' => base64_encode($basic_auth_login . ":" . $basic_auth_password)
                        ],
                        'body'   => '<?xml version="1.0" encoding="UTF-8" ?>' .
                        '<REQUEST>' .
                          '<DEVICEID>mydeviceuniqueid</DEVICEID>' .
                          '<QUERY>PROLOG</QUERY>' .
                        '</REQUEST>'
                    ]
                );
            }
        );
        $this->object($this->exception)->isInstanceOf(RequestException::class);
        $this->object($response = $this->exception->getResponse())->isInstanceOf(Response::class);
        $this->checkJsonResponse($response, '{"status":"error","message":"Access denied. Wrong login or password for basic authentication.","expiration":24}', 401);
    }

    public function testAuthBasicWithFakeCredential()
    {
        /** @var mixed $DB */
        global $DB;

        $basic_auth_password = "a_password";
        $basic_auth_login = "a_login";

        $this->login();
        $conf = new \Glpi\Inventory\Conf();
        $this->boolean($conf->saveConf(
            [
                "enabled_inventory" => true,
                'auth_required' => \Glpi\Inventory\Conf::BASIC_AUTH,
                'basic_auth_login' => $basic_auth_login,
                'basic_auth_password' => $basic_auth_password
            ]
        ))->isTrue();
        $DB->commit();
        $this->logout();

        //first call should be unauthorized and return 401
        $this->exception(
            function () {
                $this->http_client->request(
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
            }
        );
        $this->object($this->exception)->isInstanceOf(RequestException::class);
        $this->object($response = $this->exception->getResponse())->isInstanceOf(Response::class);
        $this->checkJsonResponse($response, '{"status":"error","message":"Authorization header required to send an inventory","expiration":24}', 401);

        //second attempt should be unauthorized and return 401
        $this->exception(
            function () {
                $this->http_client->request(
                    'POST',
                    $this->base_uri . 'front/inventory.php',
                    [
                        'headers' => [
                            'Content-Type' => 'application/xml',
                            'Authorization' => base64_encode("Basic wrong_login:wrong_password")
                        ],
                        'body'   => '<?xml version="1.0" encoding="UTF-8" ?>' .
                        '<REQUEST>' .
                          '<DEVICEID>mydeviceuniqueid</DEVICEID>' .
                          '<QUERY>PROLOG</QUERY>' .
                        '</REQUEST>'
                    ]
                );
            }
        );
        $this->object($this->exception)->isInstanceOf(RequestException::class);
        $this->object($response = $this->exception->getResponse())->isInstanceOf(Response::class);
        $this->checkJsonResponse($response, '{"status":"error","message":"Access denied. Wrong login or password for basic authentication.","expiration":24}', 401);
    }
}
