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

use Glpi\Kernel\Listener\RequestListener\RemoveInternalInputKeysListener;
use Glpi\Tests\GLPITestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

class RemoveInternalInputKeysListenerTest extends GLPITestCase
{
    public function tearDown(): void
    {
        $_GET = $_POST = $_REQUEST = [];
        parent::tearDown();
    }

    private function makeRequestEvent(Request $request, int $type): RequestEvent
    {
        return new RequestEvent($this->createStub(KernelInterface::class), $request, $type);
    }

    public function testInternalKeysAreRemovedFromTheMainRequest(): void
    {
        $post = ['id' => 12, 'global_validation' => 3, '_rule_process' => 1, '_auto_update' => 1];
        $get  = ['id' => 12, '_auto_import' => 1, '_from_assignment' => 1, '_from_itilvalidation' => 1];
        $_POST = $post;
        $_GET = $get;
        $_REQUEST = $get + $post;
        $request = new Request($get, $post);

        (new RemoveInternalInputKeysListener())->onKernelRequest(
            $this->makeRequestEvent($request, HttpKernelInterface::MAIN_REQUEST)
        );

        $this->assertSame(['id' => 12, 'global_validation' => 3], $request->request->all());
        $this->assertSame(['id' => 12], $request->query->all());
        $this->assertSame(['id' => 12, 'global_validation' => 3], $_POST);
        $this->assertSame(['id' => 12], $_GET);
        $this->assertSame(['id' => 12, 'global_validation' => 3], $_REQUEST);
    }

    public function testListenerIsRegistered(): void
    {
        /** @var KernelInterface $kernel */
        global $kernel;

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $kernel->getContainer()->get('event_dispatcher');
        $is_registered = false;
        foreach ($dispatcher->getListeners(KernelEvents::REQUEST) as $listener) {
            if (is_array($listener) && $listener[0] instanceof RemoveInternalInputKeysListener) {
                $is_registered = true;
            }
        }

        $this->assertTrue($is_registered);
    }

    public function testSubRequestsAreNotAltered(): void
    {
        $request = new Request([], ['_rule_process' => 1]);

        (new RemoveInternalInputKeysListener())->onKernelRequest(
            $this->makeRequestEvent($request, HttpKernelInterface::SUB_REQUEST)
        );

        $this->assertSame(['_rule_process' => 1], $request->request->all());
    }
}
