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

use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Http\RequestPoliciesTrait;
use Glpi\Kernel\ListenersPriority;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SessionCheckCookieListener implements EventSubscriberInterface
{
    use RequestPoliciesTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->isFrontEndAssetEndpoint($event->getRequest())) {
            return;
        }

        // If session cookie is only available on a secure HTTPS context but request is made on an unsecured HTTP context,
        // throw an exception
        $cookie_secure = filter_var(ini_get('session.cookie_secure'), FILTER_VALIDATE_BOOLEAN);
        if ($event->getRequest()->isSecure() === false && $cookie_secure === true) {
            $exception = new BadRequestHttpException();
            $exception->setMessageToDisplay(__('The web server is configured to allow session cookies only on secured context (https). Therefore, you must access GLPI on a secured context to be able to use it.'));
            throw $exception;
        }
    }
}
