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

namespace Glpi\Kernel\Listener\ResponseListener;

use Glpi\Kernel\ListenersPriority;
use Glpi\Security\ContentSecurityPolicy\NonceContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ContentSecurityPolicyListener implements EventSubscriberInterface
{
    private const string CSP_HEADER_NAME = 'Content-Security-Policy';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [
                'onKernelResponse',
                ListenersPriority::RESPONSE_LISTENERS_PRIORITIES[self::class],
            ],
        ];
    }

    public function __construct(private NonceContext $nonce_context) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Run only once per http request
        if (!$event->isMainRequest()) {
            return;
        }

        // Read response state
        $response = $event->getResponse();
        $headers = $response->headers;

        // Stop here is a CSP is already defined
        if ($headers->has(self::CSP_HEADER_NAME)) {
            return;
        }

        // Only applies to request returning unsafe content
        if ($this->isSafeContent($headers->get('Content-Type'))) {
            return;
        }

        // Apply policy into the headers
        $headers->set(self::CSP_HEADER_NAME, $this->getPolicy());
    }

    private function isSafeContent(?string $content_type): bool
    {
        return $content_type !== null // Default to HTML if not specified
            && !\str_starts_with(\strtolower($content_type), 'text/html')
            && !\str_starts_with(\strtolower($content_type), 'image/svg+xml')
            && !\str_starts_with(\strtolower($content_type), 'application/xhtml+xml')
        ;
    }

    private function getPolicy(): string
    {
        $nonce = $this->nonce_context->getNonce();

        $policy = [
            "base-uri 'self'",
            "script-src 'nonce-$nonce' 'unsafe-eval'",
            "object-src 'none'",
            "worker-src 'self' blob:",
        ];

        return \implode("; ", $policy);
    }
}
