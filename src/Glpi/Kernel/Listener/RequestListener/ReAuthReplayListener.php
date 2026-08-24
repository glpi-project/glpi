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

declare(strict_types=1);

namespace Glpi\Kernel\Listener\RequestListener;

use Glpi\Kernel\ListenersPriority;
use Glpi\Security\ReAuth\ReAuthManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Restores the referer of a request replayed after a successful re-authentication.
 *
 * A re-authentication interrupts the user's navigation: the requested action is recorded, the
 * prompt is displayed, then the request is replayed by the verification page through an
 * auto-submitted form. The browser therefore reports the verification page as the referer, and
 * anything relying on it — `Html::back()` most notably — would send the user back into the
 * re-authentication flow instead of the page they came from.
 *
 * The origin page is read from the session, never from the request: the replay only carries the
 * {@see ReAuthManager::RESTORE_REFERER_PARAM} parameter, which holds no exploitable value, so a
 * forged one can at most send the user back to their own origin page.
 *
 * Restoring the referer here, rather than teaching `Html::getRefererUrl()` about
 * re-authentication, keeps that knowledge inside the re-authentication domain: `ReAuthManager`
 * already depends on `Html`, and the reverse dependency would close a cycle between them.
 */
final readonly class ReAuthReplayListener implements EventSubscriberInterface
{
    public function __construct(
        private ReAuthManager $reauth_manager
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // A sub-request is not the replayed request.
            return;
        }

        $request = $event->getRequest();

        // The parameter lands in the query string of a replayed GET request, and in the body of a
        // replayed POST one.
        if (
            !$request->query->has(ReAuthManager::RESTORE_REFERER_PARAM)
            && !$request->request->has(ReAuthManager::RESTORE_REFERER_PARAM)
        ) {
            return;
        }

        $origin_url = $this->reauth_manager->getOriginURL();

        // Both are updated: legacy scripts read the superglobal, while anything built on the
        // Symfony request reads the header bag.
        $request->headers->set('referer', $origin_url);
        $_SERVER['HTTP_REFERER'] = $origin_url;
    }
}
