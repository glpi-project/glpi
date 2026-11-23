<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Toolbox;

use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Validates `tools/patches/symfony-http-client-no-private-network-allow-list.patch`.
 */
class NoPrivateNetworkHttpClientPatchTest extends GLPITestCase
{
    private const PROXY_IP = '192.168.68.86';
    private const PUBLIC_URL = 'http://93.184.215.14/';
    private const PRIVATE_URL = 'http://10.0.0.5/';

    public static function requestProvider(): iterable
    {
        yield 'public target through the proxy' => [
            'url'       => self::PUBLIC_URL,
            'responses' => [new MockResponse('ok', ['primary_ip' => self::PROXY_IP])],
            'blocked'   => false,
        ];
        yield 'public target through another private IP' => [
            'url'       => self::PUBLIC_URL,
            'responses' => [new MockResponse('ok', ['primary_ip' => '192.168.68.87'])],
            'blocked'   => true,
        ];
        yield 'private target through the proxy' => [
            'url'       => self::PRIVATE_URL,
            'responses' => [new MockResponse('ok', ['primary_ip' => self::PROXY_IP])],
            'blocked'   => true,
        ];
        yield 'redirect to a private target through the proxy' => [
            'url'       => self::PUBLIC_URL,
            'responses' => [
                new MockResponse('', [
                    'http_code'        => 302,
                    'primary_ip'       => self::PROXY_IP,
                    'redirect_url'     => self::PRIVATE_URL,
                    'response_headers' => ['Location' => self::PRIVATE_URL],
                ]),
                new MockResponse('ok', ['primary_ip' => self::PROXY_IP]),
            ],
            'blocked'   => true,
        ];
    }

    #[DataProvider('requestProvider')]
    public function testRequest(string $url, array $responses, bool $blocked): void
    {
        $client = new NoPrivateNetworkHttpClient(new MockHttpClient($responses), null, [self::PROXY_IP]);

        if ($blocked) {
            $this->expectException(TransportException::class);
        }

        $this->assertSame('ok', $client->request('GET', $url)->getContent());
    }
}
