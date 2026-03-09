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

namespace tests\units\Glpi\Api\HL\Middleware;

use Glpi\Api\HL\Middleware\IPRestrictionRequestMiddleware;
use Glpi\Api\HL\Middleware\MiddlewareInput;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\Router;
use Glpi\Api\HL\RoutePath;
use Glpi\Http\Request;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class IPRestrictionRequestMiddlewareTest extends DbTestCase
{
    public static function isIPAllowedProvider()
    {
        return [
            ['ip' => '127.0.0.1', 'allowed_ips' => '127.0.0.1', 'expected' => true],
            ['ip' => '127.0.0.1', 'allowed_ips' => '::1', 'expected' => false],
            ['ip' => '127.0.0.1', 'allowed_ips' => '::1,127.0.0.1', 'expected' => true],
            ['ip' => '10.10.13.5', 'allowed_ips' => '10.10.13.0/24', 'expected' => true],
            ['ip' => '10.10.13.5', 'allowed_ips' => '10.10.13.0/16', 'expected' => true],
            ['ip' => '10.10.13.5', 'allowed_ips' => '10.10.13.0/32', 'expected' => false],
            ['ip' => '10.10.13.5', 'allowed_ips' => '10.10.13.5/32', 'expected' => true],
            ['ip' => '2001:4860:4860::8888', 'allowed_ips' => '2001:4860:4860::8888/32', 'expected' => true],
            ['ip' => '2001:4860:4860::8888', 'allowed_ips' => '2001:4860:4860::8888/64', 'expected' => true],
            ['ip' => '2001:4860:4860::8888', 'allowed_ips' => '2001:4860:4860::8888/128', 'expected' => true],
            ['ip' => '2001:4861:4860::8888', 'allowed_ips' => '2001:4860:4860::8888/32', 'expected' => false],
            ['ip' => '::1', 'allowed_ips' => '2001:4860:4860::8888/64,::1', 'expected' => true],
        ];
    }

    #[DataProvider('isIPAllowedProvider')]
    public function testIsIPAllowed($ip, $allowed_ips, $expected)
    {
        $middleware = new IPRestrictionRequestMiddleware();
        $this->assertEquals($expected, $this->callPrivateMethod($middleware, 'isIPAllowed', $ip, $allowed_ips));
    }

    public static function isCidrMatchProvider()
    {
        return [
            ['ip' => '10.10.13.5', 'range' => '10.10.13.0/24', 'expected' => true],
            ['ip' => '10.10.13.5', 'range' => '10.10.13.0/16', 'expected' => true],
            ['ip' => '10.10.13.5', 'range' => '10.10.13.0/32', 'expected' => false],
            ['ip' => '10.10.13.5', 'range' => '10.10.13.5/32', 'expected' => true],
            ['ip' => '2001:4860:4860::8888', 'range' => '2001:4860:4860::8888/32', 'expected' => true],
            ['ip' => '2001:4860:4860::8888', 'range' => '2001:4860:4860::8888/64', 'expected' => true],
            ['ip' => '2001:4861:4860::8888', 'range' => '2001:4860:4860::8888/32', 'expected' => false],
        ];
    }

    #[DataProvider('isCidrMatchProvider')]
    public function testIsCidrMatch($ip, $range, $expected)
    {
        $middleware = new IPRestrictionRequestMiddleware();
        $this->assertEquals($expected, $this->callPrivateMethod($middleware, 'isCidrMatch', $ip, $range));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function processProvider(): array
    {
        return [
            'token_endpoint_no_ip_restriction' => [
                'allowed_ips'            => null,
                'remote_addr'            => '127.0.0.1',
                'route_path'             => '/token',
                'authenticated_client'   => false,
                'expected_next_called'   => true,
                'expected_status_code'   => null,
            ],
            'token_endpoint_authorized_ip' => [
                'allowed_ips'            => '127.0.0.1',
                'remote_addr'            => '127.0.0.1',
                'route_path'             => '/token',
                'authenticated_client'   => false,
                'expected_next_called'   => true,
                'expected_status_code'   => null,
            ],
            'token_endpoint_unauthorized_ip' => [
                'allowed_ips'            => '192.168.99.99',
                'remote_addr'            => '127.0.0.1',
                'route_path'             => '/token',
                'authenticated_client'   => false,
                'expected_next_called'   => false,
                'expected_status_code'   => 401,
            ],
            'api_request_no_ip_restriction' => [
                'allowed_ips'            => null,
                'remote_addr'            => '127.0.0.1',
                'route_path'             => '/Ticket',
                'authenticated_client'   => true,
                'expected_next_called'   => true,
                'expected_status_code'   => null,
            ],
            'api_request_authorized_ip' => [
                'allowed_ips'            => '127.0.0.1',
                'remote_addr'            => '127.0.0.1',
                'route_path'             => '/Ticket',
                'authenticated_client'   => true,
                'expected_next_called'   => true,
                'expected_status_code'   => null,
            ],
            'api_request_unauthorized_ip' => [
                'allowed_ips'            => '127.0.0.1',
                'remote_addr'            => '192.168.99.1',
                'route_path'             => '/Ticket',
                'authenticated_client'   => true,
                'expected_next_called'   => false,
                'expected_status_code'   => 403,
            ],
            'status_all_no_ip_restriction_unauthenticated' => [
                'allowed_ips'            => null,
                'remote_addr'            => '127.0.0.1',
                'route_path'             => '/status/all',
                'authenticated_client'   => false,
                'expected_next_called'   => true,
                'expected_status_code'   => null,
            ],
            'status_all_unauthorized_ip_unauthenticated' => [
                'allowed_ips'            => '192.168.99.99',
                'remote_addr'            => '127.0.0.1',
                'route_path'             => '/status/all',
                'authenticated_client'   => false,
                'expected_next_called'   => true,
                'expected_status_code'   => null,
            ],
            'status_all_unauthorized_ip_authenticated' => [
                'allowed_ips'            => '127.0.0.1',
                'remote_addr'            => '192.168.99.1',
                'route_path'             => '/status/all',
                'authenticated_client'   => true,
                'expected_next_called'   => false,
                'expected_status_code'   => 403,
            ],
        ];
    }

    #[DataProvider('processProvider')]
    public function testProcess(
        ?string $allowed_ips,
        string $remote_addr,
        string $route_path,
        bool $authenticated_client,
        bool $expected_next_called,
        ?int $expected_status_code,
    ): void {
        $client_oauth = $this->createItem(\OAuthClient::class, [
            'name' => __FUNCTION__ . '_client',
            'is_active'       => 1,
            'is_confidential' => 1,
            'grants'          => ['client_credentials'],
            'scopes'          => ['api'],
            'allowed_ips'     => $allowed_ips,
        ]);

        $identifier = $client_oauth->getField('identifier');

        $_SERVER['REMOTE_ADDR'] = $remote_addr;

        $router = Router::getInstance();
        $this->setPrivateProperty(
            $router,
            'current_client',
            $authenticated_client ? ['client_id' => $identifier] : null
        );

        $request = new Request('POST', $route_path);
        if (!$authenticated_client) {
            $request->setParameter('client_id', $identifier);
        }

        $route = new RoutePath(\Glpi\Api\HL\Controller\CoreController::class, 'token', $route_path, ['POST'], 1, Route::SECURITY_NONE, '');
        $input = new MiddlewareInput($request, $route, null);

        $next_called = false;
        $middleware = new IPRestrictionRequestMiddleware();
        $middleware->process($input, function () use (&$next_called): void {
            $next_called = true;
        });

        $this->assertEquals($expected_next_called, $next_called);
        if (!$expected_next_called) {
            $this->assertEquals($expected_status_code, $input->response->getStatusCode());
        }
    }
}
