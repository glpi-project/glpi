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

namespace tests\units\Glpi\Kernel\Listener\ExceptionListener;

use Glpi\Exception\SessionExpiredException;
use Glpi\Http\RedirectResponse;
use Glpi\Kernel\Listener\ExceptionListener\AccessErrorListener;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class AccessErrorListenerTest extends DbTestCase
{
    /**
     * @return iterable<string, array{method: string, uri: string, redirect: string}>
     */
    public static function sessionExpiredProvider(): iterable
    {
        yield 'GET' => [
            'method'   => 'GET',
            'uri'      => '/front/computer.form.php?id=5',
            'redirect' => '/front/computer.form.php?id=5',
        ];
        yield 'HEAD' => [
            'method'   => 'HEAD',
            'uri'      => '/front/computer.form.php?id=5',
            'redirect' => '/front/computer.form.php?id=5',
        ];
        // The original URL must not be used as redirection target, as it will be requested using the `GET` method.
        yield 'POST' => [
            'method'   => 'POST',
            'uri'      => '/Session/ChangeProfile',
            'redirect' => '/',
        ];
        yield 'POST with query string' => [
            'method'   => 'POST',
            'uri'      => '/front/computer.form.php?id=5',
            'redirect' => '/',
        ];
        yield 'PUT' => [
            'method'   => 'PUT',
            'uri'      => '/front/computer.form.php?id=5',
            'redirect' => '/',
        ];
        yield 'DELETE' => [
            'method'   => 'DELETE',
            'uri'      => '/front/computer.form.php?id=5',
            'redirect' => '/',
        ];
    }

    #[DataProvider('sessionExpiredProvider')]
    public function testSessionExpiredRedirection(string $method, string $uri, string $redirect): void
    {
        $this->login();

        $request = Request::create($uri, $method);

        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new SessionExpiredException()
        );

        (new AccessErrorListener())->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            \sprintf('/?redirect=%s&error=3', \rawurlencode($redirect)),
            $response->getTargetUrl()
        );

        // Session must have been destroyed.
        $this->assertEmpty($_SESSION);
    }
}
