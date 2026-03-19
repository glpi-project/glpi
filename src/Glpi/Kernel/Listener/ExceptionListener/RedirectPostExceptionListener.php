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

namespace Glpi\Kernel\Listener\ExceptionListener;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\RedirectPostException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class RedirectPostExceptionListener implements EventSubscriberInterface
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
        $throwable = $event->getThrowable();

        if (!$throwable instanceof RedirectPostException) {
            return;
        }

        // The original CSRF token was already consumed during the initial request validation.
        // Remove it so the Twig template can inject a fresh one via {{ csrf_token() }}.
        $post_data = $throwable->getPost();
        unset($post_data['_glpi_csrf_token']);

        // Carry the original URL as a POST field so that Html::getRefererUrl() can return
        // the correct "back" URL on the replayed request.
        // Without this, the browser would send Referer: /ReAuth/Verify (the page that served
        // this auto-submit form), causing Html::back() to redirect to the wrong place.
        $post_data['_glpi_http_referer'] = $throwable->getUrl();

        $response = new Response(
            TemplateRenderer::getInstance()->render('pages/redirect_post.html.twig', [
                'url'       => $throwable->getUrl(),
                'post_data' => $post_data,
            ])
        );


        $event->setResponse($response);
    }
}

