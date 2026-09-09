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

namespace Glpi\Kernel\Listener\RequestListener;

use Glpi\Error\StartupErrors;
use Glpi\Exception\Http\HttpException;
use Glpi\Kernel\ListenersPriority;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Report the PHP errors that occurred during the request startup.
 *
 * @see StartupErrors
 */
final class CheckStartupErrorsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // The startup errors are related to the whole PHP request, they are already
            // handled during the main request.
            return;
        }

        $error = StartupErrors::get();
        if ($error === null) {
            return;
        }

        // Startup errors are raised before the error handler is registered, so they never reach
        // the GLPI logs. Log them explicitly, otherwise there would be no trace of them.
        \trigger_error(\sprintf('PHP startup error: %s', $error['message']), E_USER_WARNING);

        $reason = StartupErrors::getTruncationReason();
        if ($reason === null) {
            // The error did not alter the input data, the request can still be processed.
            return;
        }

        // A part of the input data has been dropped by PHP. Processing the request would make
        // GLPI operate on incomplete data  so it has to be rejected with an explicit message.
        $exception = new HttpException($reason->getStatusCode());
        $exception->setMessageToDisplay($reason->getMessageToDisplay());
        throw $exception;
    }
}
