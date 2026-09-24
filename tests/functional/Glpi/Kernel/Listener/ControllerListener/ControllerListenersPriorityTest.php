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

namespace tests\units\Glpi\Kernel\Listener\ControllerListener;

use Glpi\Controller\Session\ChangeProfileController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\SessionExpiredException;
use Glpi\Http\Firewall;
use Glpi\Http\SessionManager;
use Glpi\Kernel\Listener\ControllerListener\CheckCsrfListener;
use Glpi\Kernel\Listener\ControllerListener\FirewallStrategyListener;
use Glpi\Tests\DbTestCase;
use Session;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ControllerListenersPriorityTest extends DbTestCase
{
    public function testFirewallIsExecutedBeforeCsrfCheck(): void
    {
        $session_manager = new SessionManager();
        $csrf_listener = new CheckCsrfListener($session_manager);
        $firewall_listener = new FirewallStrategyListener(new Firewall(), $session_manager);

        $dispatcher = new EventDispatcher();
        // Register the listeners in the reverse order of the expected execution order, to be sure that
        // the execution order does not depend on the registration order.
        $dispatcher->addSubscriber($csrf_listener);
        $dispatcher->addSubscriber($firewall_listener);

        $this->assertSame(
            [
                [$firewall_listener, 'onKernelController'],
                [$csrf_listener, 'onKernelController'],
            ],
            $dispatcher->getListeners(KernelEvents::CONTROLLER)
        );
    }

    public function testPostRequestWithExpiredSessionThrowsSessionExpiredException(): void
    {
        // No session, thus no CSRF token.
        $this->assertFalse(Session::getLoginUserID());
        $this->assertArrayNotHasKey('glpicsrftokens', $_SESSION);

        $this->expectException(SessionExpiredException::class);

        $this->dispatch(Request::create('/Session/ChangeProfile', 'POST', ['id' => 1]));
    }

    public function testPostRequestWithInvalidCsrfTokenIsStillDenied(): void
    {
        $this->login();

        $this->expectException(AccessDeniedHttpException::class);

        $this->dispatch(Request::create('/Session/ChangeProfile', 'POST', ['id' => 1, '_glpi_csrf_token' => 'invalid']));
    }

    public function testPostRequestWithValidCsrfTokenIsAccepted(): void
    {
        $this->login();

        $token = Session::getNewCSRFToken();

        $this->dispatch(Request::create('/Session/ChangeProfile', 'POST', ['id' => 1, '_glpi_csrf_token' => $token]));

        // Token has been validated and consumed.
        $this->assertArrayNotHasKey($token, $_SESSION['glpicsrftokens']);
    }

    private function getDispatcher(): EventDispatcher
    {
        $session_manager = new SessionManager();

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new CheckCsrfListener($session_manager));
        $dispatcher->addSubscriber(new FirewallStrategyListener(new Firewall(), $session_manager));

        return $dispatcher;
    }

    private function dispatch(Request $request): void
    {
        // The controller is not executed, only its attributes are read by the firewall.
        $event = new ControllerEvent(
            $this->createStub(HttpKernelInterface::class),
            new ChangeProfileController(),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->getDispatcher()->dispatch($event, KernelEvents::CONTROLLER);
    }
}
