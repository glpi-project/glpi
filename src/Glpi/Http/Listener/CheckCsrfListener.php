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

namespace Glpi\Http\Listener;

use Glpi\Security\Attribute\DisableCsrfChecks;
use Session;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class CheckCsrfListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var DisableCsrfChecks[] $attributes */
        $attributes = $event->getAttributes(DisableCsrfChecks::class);
        if (\count($attributes) > 0) {
            // CSRF checks are explicitely disabled for this controller.
            return;
        }

        $request = $event->getRequest();

        $bodyless_methods = [
            Request::METHOD_GET,
            Request::METHOD_HEAD,
            Request::METHOD_OPTIONS,
            Request::METHOD_TRACE,
        ];
        if (in_array($request->getRealMethod(), $bodyless_methods)) {
            // No CSRF checks if method is not supposed to have a body.
            return;
        }

        if ($request->isXmlHttpRequest()) {
            // Keep CSRF token as many AJAX requests may be made at the same time.
            // This is due to the fact that read operations are often made using POST method (see #277).
            define('GLPI_KEEP_CSRF_TOKEN', true);

            // For AJAX requests, check CSRF token located into "X-Glpi-Csrf-Token" header.
            Session::checkCSRF(['_glpi_csrf_token' => $request->server->get('HTTP_X_GLPI_CSRF_TOKEN') ?? '']);
        } else {
            Session::checkCSRF($request->request->all());
        }
    }
}
