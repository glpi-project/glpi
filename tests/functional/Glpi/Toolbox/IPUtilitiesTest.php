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

use Glpi\Kernel\Kernel;
use Glpi\Kernel\Listener\PostBootListener\ConfigureTrustedProxies;
use Glpi\Tests\GLPITestCase;
use Glpi\Toolbox\IPUtilities;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;

class IPUtilitiesTest extends GLPITestCase
{
    private array $trusted_proxies;
    private int $trusted_header_set;

    public function setUp(): void
    {
        parent::setUp();

        $this->trusted_proxies = Request::getTrustedProxies();
        $this->trusted_header_set = Request::getTrustedHeaderSet();
    }

    public function tearDown(): void
    {
        Request::setTrustedProxies($this->trusted_proxies, $this->trusted_header_set);

        parent::tearDown();
    }

    public function testConfigureTrustedProxies(): void
    {
        IPUtilities::configureTrustedProxies(
            ['10.10.1.3', '192.168.0.0/16'],
            ['Forwarded', 'x-forwarded-for', 'X-FORWARDED-PROTO']
        );

        $this->assertSame(['10.10.1.3', '192.168.0.0/16'], Request::getTrustedProxies());
        $this->assertSame(
            Request::HEADER_FORWARDED | Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO,
            Request::getTrustedHeaderSet()
        );
    }

    public function testConfigureTrustedProxiesWithUnsupportedHeader(): void
    {
        IPUtilities::configureTrustedProxies(['10.10.1.3'], ['X-Real-IP', 'X-Forwarded-For']);

        $this->assertSame(Request::HEADER_X_FORWARDED_FOR, Request::getTrustedHeaderSet());
        $this->hasPhpLogRecordThatContains(
            'The "X-Real-IP" reverse proxy header is not supported and will be ignored.',
            'warning'
        );
    }

    public static function clientIPProvider(): iterable
    {
        $proxies = ['10.10.1.3', '10.20.0.0/16', 'fd79:a3b1:c4d2:1::1'];

        yield 'no remote address' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => [],
            'expected' => null,
        ];

        yield 'untrusted remote address' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => '10.8.4.5', 'HTTP_X_FORWARDED_FOR' => '203.0.113.5'],
            'expected' => '10.8.4.5',
        ];

        yield 'trusted proxy without header' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => '10.10.1.3'],
            'expected' => '10.10.1.3',
        ];

        yield 'trusted proxy with header' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => '10.10.1.3', 'HTTP_X_FORWARDED_FOR' => '203.0.113.5'],
            'expected' => '203.0.113.5',
        ];

        yield 'trusted proxy in a CIDR range' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => '10.20.4.4', 'HTTP_X_FORWARDED_FOR' => '203.0.113.5'],
            'expected' => '203.0.113.5',
        ];

        yield 'trusted proxy in a non canonical form' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => 'FD79:A3B1:C4D2:0001:0:0:0:1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.5'],
            'expected' => '203.0.113.5',
        ];

        yield 'client supplied entries are ignored' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => '10.10.1.3', 'HTTP_X_FORWARDED_FOR' => '6.6.6.6, 203.0.113.5'],
            'expected' => '203.0.113.5',
        ];

        yield 'chained trusted proxies' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => '10.10.1.3', 'HTTP_X_FORWARDED_FOR' => '6.6.6.6, 203.0.113.5, 10.20.4.4'],
            'expected' => '203.0.113.5',
        ];

        yield 'chained untrusted proxy' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => '10.10.1.3', 'HTTP_X_FORWARDED_FOR' => '203.0.113.5, 198.51.100.7'],
            'expected' => '198.51.100.7',
        ];

        yield 'only trusted proxies in the chain' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => '10.10.1.3', 'HTTP_X_FORWARDED_FOR' => '10.20.4.4, 10.20.5.5'],
            'expected' => '10.20.4.4',
        ];

        yield 'invalid entries are ignored' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => '10.10.1.3', 'HTTP_X_FORWARDED_FOR' => '203.0.113.5, unknown, '],
            'expected' => '203.0.113.5',
        ];

        yield 'port is removed' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => '10.10.1.3', 'HTTP_X_FORWARDED_FOR' => '203.0.113.5:51234'],
            'expected' => '203.0.113.5',
        ];

        yield 'IPv6 brackets and port are removed' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => 'fd79:a3b1:c4d2:1::1', 'HTTP_X_FORWARDED_FOR' => '[2001:db8::17]:4711'],
            'expected' => '2001:db8::17',
        ];

        yield 'untrusted header' => [
            'proxies'  => $proxies,
            'headers'  => ['X-Forwarded-For'],
            'server'   => ['REMOTE_ADDR' => '10.10.1.3', 'HTTP_FORWARDED' => 'for=203.0.113.5;proto=http'],
            'expected' => '10.10.1.3',
        ];

        yield 'Forwarded header' => [
            'proxies'  => $proxies,
            'headers'  => ['Forwarded'],
            'server'   => ['REMOTE_ADDR' => '10.10.1.3', 'HTTP_FORWARDED' => 'for=6.6.6.6, For="[fd79:a3b1:c4d2:1::5]:4711";proto=http'],
            'expected' => 'fd79:a3b1:c4d2:1::5',
        ];

        yield 'Forwarded header with a client supplied quote' => [
            'proxies'  => $proxies,
            'headers'  => ['Forwarded'],
            'server'   => ['REMOTE_ADDR' => '10.10.1.3', 'HTTP_FORWARDED' => 'for="6.6.6.6, for=203.0.113.5'],
            'expected' => '10.10.1.3',
        ];

        yield 'matching Forwarded and X-Forwarded-For headers' => [
            'proxies'  => $proxies,
            'headers'  => ['Forwarded', 'X-Forwarded-For'],
            'server'   => [
                'REMOTE_ADDR'          => '10.10.1.3',
                'HTTP_FORWARDED'       => 'for=203.0.113.5',
                'HTTP_X_FORWARDED_FOR' => '203.0.113.5',
            ],
            'expected' => '203.0.113.5',
        ];

        yield 'conflicting Forwarded and X-Forwarded-For headers' => [
            'proxies'  => $proxies,
            'headers'  => ['Forwarded', 'X-Forwarded-For'],
            'server'   => [
                'REMOTE_ADDR'          => '10.10.1.3',
                'HTTP_FORWARDED'       => 'for=203.0.113.5',
                'HTTP_X_FORWARDED_FOR' => '198.51.100.7',
            ],
            'expected' => null,
        ];
    }

    #[DataProvider('clientIPProvider')]
    public function testGetClientIP(array $proxies, array $headers, array $server, ?string $expected): void
    {
        IPUtilities::configureTrustedProxies($proxies, $headers);

        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_FORWARDED'], $_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER = $server + $_SERVER;

        $this->assertSame($expected, IPUtilities::getClientIP());
    }

    public function testGetClientIPWithConflictingHeadersOnMainRequest(): void
    {
        /** @var Kernel $kernel */
        global $kernel;

        IPUtilities::configureTrustedProxies(['10.10.1.3'], ['Forwarded', 'X-Forwarded-For']);

        $request = new Request(server: [
            'REMOTE_ADDR'          => '10.10.1.3',
            'HTTP_FORWARDED'       => 'for=203.0.113.5',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.7',
        ]);
        $this->setPrivateProperty($kernel, 'main_request', $request);

        try {
            // The conflict is reported only once by Symfony, the next calls must still fail.
            $this->assertNull(IPUtilities::getClientIP());
            $this->assertNull(IPUtilities::getClientIP());
        } finally {
            $this->setPrivateProperty($kernel, 'main_request', null);
        }
    }

    public function testConfigureTrustedProxiesListener(): void
    {
        Request::setTrustedProxies([], 0);

        (new ConfigureTrustedProxies())->onPostBoot();

        $this->assertSame(GLPI_TRUSTED_REVERSE_PROXIES, Request::getTrustedProxies());
        $this->assertSame(Request::HEADER_X_FORWARDED_FOR, Request::getTrustedHeaderSet());
    }

    public function testIsTrustedReverseProxy(): void
    {
        IPUtilities::configureTrustedProxies(['10.10.1.3', 'fd79:a3b1:c4d2:1::/64'], ['X-Forwarded-For']);

        $cases = [
            ['10.10.1.3', true],
            ['fd79:a3b1:c4d2:1::5', true],
            ['10.9.1.3', false],
            [null, false],
        ];
        foreach ($cases as [$ip, $expected]) {
            $this->assertSame($expected, IPUtilities::isTrustedReverseProxy($ip));
            $this->hasPhpLogRecordThatContains(
                'Use `Symfony\\Component\\HttpFoundation\\Request::isFromTrustedProxy()` instead.',
                LogLevel::INFO
            );
        }
    }

    public function testCidrToRange(): void
    {
        $this->assertEquals(['10.10.0.0', '10.10.255.255'], IPUtilities::cidrToRange('10.10.4.0/16'));
        $this->assertEquals(['fd79:a3b1:c4d2:1::', 'fd79:a3b1:c4d2:1:ffff:ffff:ffff:ffff'], IPUtilities::cidrToRange('fd79:a3b1:c4d2:1::/64'));
        $this->assertEquals(['8.0.0.0', '11.255.255.255'], IPUtilities::cidrToRange('10.10.4.0/6'));
    }

    public function testIsCidrMatch(): void
    {
        $cases = [
            ['10.10.54.0', '10.10.4.0/16', true],
            ['8.10.4.0', '10.10.4.0/6', true],
            ['7.10.4.0', '10.10.4.0/6', false],
            ['fd79:a3b1:c4d2:1::5', 'fd79:a3b1:c4d2:1::/64', true],
            ['fd79:a3b1:c4d2:2::5', 'fd79:a3b1:c4d2:1::/64', false],
        ];
        foreach ($cases as [$ip, $range, $expected]) {
            $this->assertSame($expected, IPUtilities::isCidrMatch(ip: $ip, range: $range));
            $this->hasPhpLogRecordThatContains(
                'Use `Glpi\\Toolbox\\IPUtilities::isIPInList()` instead.',
                LogLevel::INFO
            );
        }
    }

    public function testIsIPInList(): void
    {
        $this->assertTrue(IPUtilities::isIPInList(ip: '8.10.4.0', allowed_ips: ['8.10.4.0']));
        $this->assertTrue(IPUtilities::isIPInList(ip: '8.10.4.0', allowed_ips: ['10.10.4.0/6']));
        $this->assertFalse(IPUtilities::isIPInList(ip: '7.10.4.0', allowed_ips: ['10.10.4.0/6']));
        $this->assertTrue(IPUtilities::isIPInList(ip: 'fd79:a3b1:c4d2:1::5', allowed_ips: ['fd79:a3b1:c4d2:1::/64']));
        $this->assertFalse(IPUtilities::isIPInList(ip: 'fd79:a3b1:c4d2:2::5', allowed_ips: ['fd79:a3b1:c4d2:1::/64']));

        // IPs are compared in their canonical form
        $this->assertTrue(IPUtilities::isIPInList(ip: 'FD79:A3B1:C4D2:0001:0:0:0:5', allowed_ips: ['fd79:a3b1:c4d2:1::5']));

        // IPv4 and IPv6 are never mixed
        $this->assertFalse(IPUtilities::isIPInList(ip: 'a00::1', allowed_ips: ['10.0.0.0/8']));
        $this->assertFalse(IPUtilities::isIPInList(ip: '10.0.0.1', allowed_ips: ['a00::/8']));

        // Invalid values never match
        $this->assertFalse(IPUtilities::isIPInList(ip: 'unknown', allowed_ips: ['10.0.0.0/8', 'unknown']));
    }
}
