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

/**
 * Test class for src/Glpi/Inventory/Request.php
 */
class RequestTest extends \GLPITestCase
{
    public function testConstructor()
    {
       //no mode without content
        $request = new \Glpi\Inventory\Request();
        $this->assertNull($request->getMode());
        $this->assertSame("", $request->getResponse());

       //no mode with content
        $request->addToResponse(["something" => "some content"]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mode has not been set');
        $this->assertNull($request->getResponse());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mode has not been set');
        $this->assertNull($request->getContentType());

        //XML mode
        $request = new \Glpi\Inventory\Request();
        $request->handleContentType('application/xml');
        $this->assertSame(\Glpi\Inventory\Request::XML_MODE, $request->getMode());
        $this->assertSame("<?xml version=\"1.0\"?>\n<REPLY/>", $request->getResponse());
        $this->assertSame('application/xml', $request->getContentType());

       //JSON mode
        $request = new \Glpi\Inventory\Request();
        $request->handleContentType('application/json');
        $this->assertSame(\Glpi\Inventory\Request::JSON_MODE, $request->getMode());
        $response = [];
        $this->assertSame(json_encode($response), $request->getResponse());
        $this->assertSame('application/json', $request->getContentType());
    }

    public function testProlog()
    {
        $data = "<?xml version=\"1.0\"?>\n<REQUEST><DEVICEID>atoumized-device</DEVICEID><QUERY>PROLOG</QUERY></REQUEST>";
        $request = new \Glpi\Inventory\Request();
        $request->handleContentType('application/xml');
        $request->handleRequest($data);
        $this->assertSame("<?xml version=\"1.0\"?>\n<REPLY><PROLOG_FREQ>24</PROLOG_FREQ><RESPONSE>SEND</RESPONSE></REPLY>", $request->getResponse());
    }

    public static function queriesProvider()
    {
        return [
            ['query' => 'INVENTORY'], //Request::INVENT_QUERY | Request::INVENT_ACTION
            ['query' => 'PROLOG'], //Request::PROLOG_QUERY
            ['query' => 'SNMPQUERY'], //Request::OLD_SNMP_QUERY
            ['query' => 'SNMP'], //Request::SNMP_QUERY
            ['query' => 'NETDISCOVERY'], //Request::NETDISCOVERY_ACTION
            ['query' => 'inventory'], //Request::INVENT_QUERY | Request::INVENT_ACTION
            ['query' => 'prolog'], //Request::PROLOG_QUERY
            ['query' => 'netinventory'], //Request::NETINV_ACTION
        ];
    }

    /**
     * Test known queries
     *
     * @dataProvider queriesProvider
     */
    public function testSnmpQuery($query)
    {
        $data = "<?xml version=\"1.0\"?>\n<REQUEST><DEVICEID>atoumized-device</DEVICEID><CONTENT><DEVICE></DEVICE></CONTENT><QUERY>$query</QUERY></REQUEST>";

        $request = $this->getMockBuilder(\Glpi\Inventory\Request::class)
            ->onlyMethods(['inventory', 'prolog'])
            ->getMock();
        $request->method('inventory')->willReturn(null);
        $request->method('prolog')->willReturn(null);

        $request->handleContentType('Application/xml');
        $request->handleRequest($data);
        $this->assertSame("<?xml version=\"1.0\"?>\n<REPLY/>", $request->getResponse());
    }

    public static function unhandledQueriesProvider()
    {
        return [
            ['query' => 'register'], //Request::REGISTER_ACTION
            ['query' => 'configuration'], //Request::CONFIG_ACTION
            ['query' => 'esx'], //Request::ESX_ACTION
            ['query' => 'collect'], //Request::COLLECT_ACTION
            ['query' => 'deploy'], //Request:: DEPLOY_ACTION
            ['query' => 'wakeonlan'], //Request::WOL_ACTION
            ['query' => 'unknown'],
        ];
    }

    /**
     * Test unknown queries
     *
     * @dataProvider unhandledQueriesProvider
     */
    public function testWrongQuery($query)
    {
        $data = "<?xml version=\"1.0\"?>\n<REQUEST><DEVICEID>atoumized-device</DEVICEID><QUERY>$query</QUERY></REQUEST>";
        $request = new \Glpi\Inventory\Request();
        $request->handleContentType('application/xml');
        $request->handleRequest($data);
        $this->assertSame(501, $request->getHttpResponseCode());
        $this->assertSame("<?xml version=\"1.0\"?>\n<REPLY><ERROR><![CDATA[Query '$query' is not supported.]]></ERROR></REPLY>", $request->getResponse());
    }

    public function testAddError()
    {
        $request = new \Glpi\Inventory\Request();
        $request->handleContentType('application/xml');
        $request->addError('Something went wrong.');
        $this->assertSame("<?xml version=\"1.0\"?>\n<REPLY><ERROR><![CDATA[Something went wrong.]]></ERROR></REPLY>", $request->getResponse());
    }

    public function testAddResponse()
    {
        $request = new \Glpi\Inventory\Request();
        $request->handleContentType('application/xml');
        //to test nodes with attributes
        $request->addToResponse([
            'OPTION' => [
                'NAME' => 'NETDISCOVERY',
                'PARAM' => [
                    'content' => '',
                    'attributes' => [
                        'THREADS_DISCOVERY' => 5,
                        'TIMEOUT' => 1,
                        'PID' => 16
                    ]
                ],
                'RANGEIP' => [
                    'content' => '',
                    'attributes' => [
                        'ID' => 1,
                        'IPSTART' => '192.168.1.1',
                        'IPEND' => '192.168.1.254',
                        'ENTITY' => 0
                    ]
                ],
                [
                    'AUTHENTICATION' => [
                        'content' => '',
                        'attributes' => [
                            'ID' => 1,
                            'COMMUNITY' => 'public',
                            'VERSION' => '1',
                            'USERNAME' => '',
                            'AUTHPROTOCOL' => '',
                            'AUTHPASSPHRASE' => '',
                            'PRIVPROTOCOL' => '',
                            'PRIVPASSPHRASE' => ''
                        ]
                    ]
                ], [
                    'AUTHENTICATION' => [
                        'content' => '',
                        'attributes' => [
                            'ID' => 2,
                            'COMMUNITY' => 'public',
                            'VERSION' => '2c',
                            'USERNAME' => '',
                            'AUTHPROTOCOL' => '',
                            'AUTHPASSPHRASE' => '',
                            'PRIVPROTOCOL' => '',
                            'PRIVPASSPHRASE' => ''
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertSame(
            "<?xml version=\"1.0\"?>\n<REPLY><OPTION><NAME>NETDISCOVERY</NAME><PARAM THREADS_DISCOVERY=\"5\" TIMEOUT=\"1\" PID=\"16\"/><RANGEIP ID=\"1\" IPSTART=\"192.168.1.1\" IPEND=\"192.168.1.254\" ENTITY=\"0\"/><AUTHENTICATION ID=\"1\" COMMUNITY=\"public\" VERSION=\"1\" USERNAME=\"\" AUTHPROTOCOL=\"\" AUTHPASSPHRASE=\"\" PRIVPROTOCOL=\"\" PRIVPASSPHRASE=\"\"/><AUTHENTICATION ID=\"2\" COMMUNITY=\"public\" VERSION=\"2c\" USERNAME=\"\" AUTHPROTOCOL=\"\" AUTHPASSPHRASE=\"\" PRIVPROTOCOL=\"\" PRIVPASSPHRASE=\"\"/></OPTION></REPLY>",
            $request->getResponse()
        );
    }

    public static function compressionProvider(): array
    {
        return [
            [
                'function' => 'gzcompress',
                'mime' => 'application/x-compress-zlib'
            ], [
                'function' => 'gzcompress',
                'mime' => 'application/x-zlib'
            ], [
                'function' => 'gzencode',
                'mime' => 'application/x-gzip'
            ], [
                'function' => 'gzencode',
                'mime' => 'application/x-compress-gzip'
            ], [
                'function' => 'gzdeflate',
                'mime' => 'application/x-compress-deflate'
            ], [
                'function' => 'gzdeflate',
                'mime' => 'application/x-deflate'
            ]
        ];
    }

    /**
     * Test request compression
     *
     * @param string $function Compression method to use
     * @param string $mime     Mime type to set
     *
     * @dataProvider compressionProvider
     *
     * @return void
     */
    public function testCompression(string $function, string $mime)
    {
        $data = "<?xml version=\"1.0\"?>\n<REQUEST><DEVICEID>atoumized-device</DEVICEID><QUERY>PROLOG</QUERY></REQUEST>";
        $cdata = $function($data);

        $request = new \Glpi\Inventory\Request();
        $request->handleContentType($mime);
        $request->handleRequest($cdata);
        $this->assertSame('atoumized-device', $request->getDeviceID());
        $this->assertSame($function("<?xml version=\"1.0\"?>\n<REPLY><PROLOG_FREQ>24</PROLOG_FREQ><RESPONSE>SEND</RESPONSE></REPLY>"), $request->getResponse());
    }
}
