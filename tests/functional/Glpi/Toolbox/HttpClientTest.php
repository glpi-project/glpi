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

use Config;
use Glpi\Tests\GLPITestCase;
use Glpi\Toolbox\HttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\TransportException;

class HttpClientTest extends GLPITestCase
{
    /** @var resource|null */
    private $fake_proxy = null;

    public static function proxiedRequestProvider(): iterable
    {
        // Only the proxy can resolve this host: the request must be sent to the proxy (unreachable here).
        yield 'host unresolvable locally, resolved through proxy' => [
            'url'                   => 'http://glpi-test.invalid/',
            'resolve_through_proxy' => true,
            'expected_error'        => '/Failed to connect to 127\.0\.0\.2 port 9/',
        ];
        // Without opt-in (e.g. URLs provided by users), the host must be resolved locally.
        yield 'host unresolvable locally, no opt-in' => [
            'url'                   => 'http://glpi-test.invalid/',
            'resolve_through_proxy' => false,
            'expected_error'        => '/Host "glpi-test\.invalid" is blocked/',
        ];
        // Hosts resolved locally are still checked against private networks.
        yield 'private IP' => [
            'url'                   => 'http://10.0.0.5/',
            'resolve_through_proxy' => true,
            'expected_error'        => '/Host "10\.0\.0\.5" is blocked/',
        ];
        yield 'localhost' => [
            'url'                   => 'http://localhost/',
            'resolve_through_proxy' => true,
            'expected_error'        => '/Host "localhost" is blocked/',
        ];
    }


    #[DataProvider('proxiedRequestProvider')]
    public function testRequestThroughProxy(string $url, bool $resolve_through_proxy, string $expected_error): void
    {
        $this->setProxy(9);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessageMatches($expected_error);

        (new HttpClient(resolve_through_proxy: $resolve_through_proxy))->get($url);
    }

    public function testUnresolvableHostIsFetchedThroughProxy(): void
    {
        $this->setProxy($this->startFakeProxy());

        $response = (new HttpClient(resolve_through_proxy: true))->get('http://glpi-test.invalid/');

        $this->assertSame('proxied', $response->getContent());
    }

    public function testNoProxyCannotBypassProxyWhenResolvedThroughProxy(): void
    {
        $this->setProxy($this->startFakeProxy());

        // Without the proxy, the request would be sent directly to 127.0.0.1 without private networks check.
        $response = (new HttpClient(resolve_through_proxy: true))->get('http://glpi-test.invalid/', [
            'no_proxy' => '*',
            'resolve'  => ['glpi-test.invalid' => '127.0.0.1'],
        ]);

        $this->assertSame('proxied', $response->getContent());
    }

    public function testStreamUnresolvableHostThroughProxy(): void
    {
        $this->setProxy($this->startFakeProxy());

        $client = new HttpClient(resolve_through_proxy: true);
        $response = $client->get('http://glpi-test.invalid/');

        $content = '';
        foreach ($client->stream($response) as $chunk) {
            $content .= $chunk->getContent();
        }
        $this->assertSame('proxied', $content);
    }

    public function testRedirectionIsNotFollowedWhenResolvedThroughProxy(): void
    {
        $this->setProxy($this->startFakeProxy());

        $this->expectException(RedirectionException::class);

        (new HttpClient(resolve_through_proxy: true))->get('http://redirect.invalid/');
    }

    public static function proxyNotUsedProvider(): iterable
    {
        yield 'no proxy configured' => [
            'proxy_name'     => '',
            'context'        => null,
            'bypass_proxy'   => false,
            'options'        => [],
        ];
        yield 'proxy bypassed' => [
            'proxy_name'     => '127.0.0.2',
            'context'        => null,
            'bypass_proxy'   => true,
            'options'        => [],
        ];
        yield 'context excluded from proxy' => [
            'proxy_name'     => '127.0.0.2',
            'context'        => Config::class,
            'bypass_proxy'   => false,
            'options'        => [],
        ];
        yield 'proxy overridden by request options' => [
            'proxy_name'     => '127.0.0.2',
            'context'        => null,
            'bypass_proxy'   => false,
            'options'        => ['proxy' => null],
        ];
    }

    /**
     * When the proxy is not used, `resolve_through_proxy` has no effect: the host must be resolved locally.
     */
    #[DataProvider('proxyNotUsedProvider')]
    public function testUnresolvableHostIsBlockedWhenProxyIsNotUsed(
        string $proxy_name,
        ?string $context,
        bool $bypass_proxy,
        array $options
    ): void {
        global $CFG_GLPI;
        $this->setProxy($this->startFakeProxy());
        $CFG_GLPI['proxy_name'] = $proxy_name;
        $CFG_GLPI['proxy_exclusions'] = [Config::class];

        $this->expectException(TransportException::class);
        $this->expectExceptionMessageMatches('/Host "glpi-test\.invalid" is blocked/');

        (new HttpClient(context: $context, bypass_proxy: $bypass_proxy, resolve_through_proxy: true))
            ->get('http://glpi-test.invalid/', $options);
    }

    public function tearDown(): void
    {
        if ($this->fake_proxy !== null) {
            proc_terminate($this->fake_proxy);
            $this->fake_proxy = null;
        }
        parent::tearDown();
    }

    /**
     * Start a fake HTTP proxy that:
     * - redirects `redirect.invalid` requests to a private IP,
     * - serves `internal` for the private IP (i.e. when a redirection is followed),
     * - serves `proxied` for any other request.
     *
     * @return int The proxy port.
     */
    private function startFakeProxy(): int
    {
        $this->fake_proxy = proc_open(
            [PHP_BINARY, '-r', <<<'PHP'
                $server = stream_socket_server('tcp://127.0.0.2:0');
                echo explode(':', stream_socket_get_name($server, false))[1], "\n";
                while ($conn = stream_socket_accept($server, 10)) {
                    $request_line = fgets($conn);
                    while (($line = fgets($conn)) !== false && trim($line) !== '');
                    fwrite($conn, match (true) {
                        str_contains($request_line, 'redirect.invalid') => "HTTP/1.1 302 Found\r\nLocation: http://10.0.0.5/\r\nContent-Length: 0\r\nConnection: close\r\n\r\n",
                        str_contains($request_line, '10.0.0.5')         => "HTTP/1.1 200 OK\r\nContent-Length: 8\r\nConnection: close\r\n\r\ninternal",
                        default                                         => "HTTP/1.1 200 OK\r\nContent-Length: 7\r\nConnection: close\r\n\r\nproxied",
                    });
                    fclose($conn);
                }
            PHP],
            [1 => ['pipe', 'w']],
            $pipes
        );

        return (int) fgets($pipes[1]);
    }

    private function setProxy(int $port): void
    {
        global $CFG_GLPI;
        $CFG_GLPI['proxy_name'] = '127.0.0.2';
        $CFG_GLPI['proxy_port'] = $port;
        $CFG_GLPI['proxy_user'] = '';
    }
}
