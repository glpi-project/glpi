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

namespace Glpi\Kernel\Listener\ControllerListener;

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Http\SessionManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function Safe\parse_url;

final readonly class CheckCsrfListener implements EventSubscriberInterface
{
    public function __construct(
        private SessionManager $session_manager
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if ($this->session_manager->isResourceStateless($event->getRequest())) {
            // Stateless resources are not subject to CSRF protection.
            return;
        }

        if (!$event->isMainRequest()) {
            // Do not check CSRF on sub-requests.
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

        if (!$this->validateRequestIsSafeFromCSRF($request)) {
            $exception = new AccessDeniedHttpException();
            $exception->setMessageToDisplay(__('The action you have requested is not allowed.'));
            throw $exception;
        }
    }

    private function validateRequestIsSafeFromCSRF(Request $request): bool
    {
        // The 'Sec-Fetch-Site' contains details about the request origin and is
        // a protected header so browsers will always send a legitimate value.
        // It is supported by all browsers since 2023.
        $sec_fetch_site = $request->headers->get('Sec-Fetch-Site');
        if ($sec_fetch_site !== null) {
            return $this->validateSecFetchSiteHeader($sec_fetch_site);
        }

        // We fallback to the 'Origin' header for older browsers (which is also
        // a protected header).
        $origin = $request->headers->get('Origin');
        $host = $request->headers->get('Host');
        if ($origin !== null && $host !== null) {
            return parse_url($origin, PHP_URL_HOST) === $host;
        }

        // If both 'Sec-Fetch-Site' and 'Origin' are missing then the request
        // did not come from a browser and thus is not subject to CSRF.
        return true;
    }

    private function validateSecFetchSiteHeader(string $sec_fetch_site): bool
    {
        return match ($sec_fetch_site) {
            // Request comes from GLPI itself.
            'same-origin' => true,

            // Request comes from an user action (e.g clicking on a bookmark).
            'none' => true,

            // Request comes form an external site.
            'cross-site' => false,

            // Request comes the same site but with a different subdomain.
            'same-site' => false,

            // Should not be possible. False to be safe.
            default => false,
        };
    }
}
