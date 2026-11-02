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

namespace tests\units\Glpi\Kernel\Listener\RequestListener;

use Glpi\Kernel\Listener\RequestListener\ConfigureTrustedProxies;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AllowMockObjectsWithoutExpectations]
final class ConfigureTrustedProxiesTest extends TestCase
{
    private array $trusted_proxies;
    private int $trusted_header_set;

    public function setUp(): void
    {
        $this->trusted_proxies = Request::getTrustedProxies();
        $this->trusted_header_set = Request::getTrustedHeaderSet();

        Request::setTrustedProxies([], 0);
    }

    public function tearDown(): void
    {
        Request::setTrustedProxies($this->trusted_proxies, $this->trusted_header_set);
    }

    public function testMainRequest(): void
    {
        $event = new RequestEvent($this->createMock(KernelInterface::class), new Request(), HttpKernelInterface::MAIN_REQUEST);
        (new ConfigureTrustedProxies())->onKernelRequest($event);

        $this->assertSame(GLPI_TRUSTED_REVERSE_PROXIES, Request::getTrustedProxies());
        $this->assertSame(Request::HEADER_X_FORWARDED_FOR, Request::getTrustedHeaderSet());
    }

    public function testSubRequest(): void
    {
        $event = new RequestEvent($this->createMock(KernelInterface::class), new Request(), HttpKernelInterface::SUB_REQUEST);
        (new ConfigureTrustedProxies())->onKernelRequest($event);

        $this->assertSame([], Request::getTrustedProxies());
        $this->assertSame(0, Request::getTrustedHeaderSet());
    }
}
