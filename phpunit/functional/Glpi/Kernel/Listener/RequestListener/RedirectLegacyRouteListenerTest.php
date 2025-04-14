<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Http\RedirectResponse;
use Glpi\Kernel\Listener\RequestListener\RedirectLegacyRouteListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class RedirectLegacyRouteListenerTest extends TestCase
{
    public static function provideLegacyUrl(): iterable
    {
        foreach (['', '/glpi', '/support/glpi'] as $root_doc) {
            yield [
                'root_doc'  => $root_doc,
                'path'      => $root_doc . '/front/helpdesk.public.php',
                'expected'  => $root_doc . '/Helpdesk',
            ];

            yield [
                'root_doc'  => $root_doc,
                'path'      => $root_doc . '/front/not.redirected.php',
                'expected'  => null,
            ];
        }
    }

    #[DataProvider('provideLegacyUrl')]
    public function testRedirection(string $root_doc, string $path, ?string $expected): void
    {
        $request = new Request();
        $request->server->set('SCRIPT_FILENAME', $root_doc . '/index.php');
        $request->server->set('SCRIPT_NAME', $root_doc . '/index.php');
        $request->server->set('REQUEST_URI', $path);

        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $listener = new RedirectLegacyRouteListener();
        $listener->onKernelRequest($event);

        if ($expected === null) {
            $this->assertNull($event->getResponse());
        } else {
            $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
            $this->assertEquals($expected, $event->getResponse()->getTargetUrl());
        }
    }
}
