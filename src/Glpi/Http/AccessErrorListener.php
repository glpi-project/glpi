<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Http;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\AuthenticationFailedException;
use Glpi\Exception\SessionExpiredException;
use Session;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AccessErrorListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // priority = 1 to be executed before the default Symfony listeners
            KernelEvents::EXCEPTION => ['onKernelException', 1],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // Ignore sub-requests.
            return;
        }

        $request = $event->getRequest();
        $throwable = $event->getThrowable();

        $response = null;

        if ($throwable instanceof SessionExpiredException) {
            Session::destroy(); // destroy the session to prevent pesistence of unexcpected data

            $response = new RedirectResponse(
                sprintf(
                    '%s/?redirect=%s&error=3',
                    $request->getBasePath(),
                    \rawurlencode($request->getPathInfo() . '?' . $request->getQueryString())
                )
            );
        } elseif (
            $throwable instanceof AccessDeniedHttpException
            && ($_SESSION['_redirected_from_profile_selector'] ?? false)
        ) {
            unset($_SESSION['_redirected_from_profile_selector']);

            $request = $event->getRequest();
            $response = new RedirectResponse(
                sprintf(
                    '%s/front/central.php',
                    $request->getBasePath()
                )
            );
        } elseif ($throwable instanceof AuthenticationFailedException) {
            $login_url = sprintf(
                '%s/front/logout.php?noAUTO=1',
                $request->getBasePath()
            );
            $redirect = $request->request->get('redirect') ?: $request->query->get('redirect') ?: null;
            if ($redirect !== null) {
                $login_url .= '&redirect=' . \rawurlencode($redirect);
            }
            $response = new Response(
                content: TemplateRenderer::getInstance()->render(
                    'pages/login_error.html.twig',
                    [
                        'errors'    => $throwable->getAuthenticationErrors(),
                        'login_url' => $login_url,
                    ]
                ),
                status: 400
            );
        }

        if ($response !== null) {
            $event->setResponse($response);
        }
    }
}
