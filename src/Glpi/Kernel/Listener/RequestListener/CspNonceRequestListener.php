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

use Glpi\Kernel\ListenersPriority;
use Glpi\Security\ContentSecurityPolicy\CspState;
use Glpi\Security\ContentSecurityPolicy\NonceContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function Safe\preg_match;

/**
 * Watch for a 'X-Glpi-Csp-Nonce' header in the request that indicate a specific
 * nonce to reuse instead of generated a random one.
 *
 * This mechanism in needed for AJAX requests that load HTML containing script (
 * which is the case for many of our tabs). These request must re-use the same
 * nonce of the main page or their script won't run.
 *
 * This is not a weakness in our CSP protection as an attacker can't add headers
 * in an XSS scenario.
 */
final class CspNonceRequestListener implements EventSubscriberInterface
{
    private const REQUEST_NONCE_HEADER_NAME = 'X-Glpi-Csp-Nonce';

    public function __construct(private NonceContext $nonce_context) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                'onKernelRequest',
                ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class],
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!CspState::isEnabled()) {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        // Look for a valid nonce.
        $nonce = $event->getRequest()->headers->get(self::REQUEST_NONCE_HEADER_NAME);
        if ($nonce === null || !$this->isValidNonce($nonce)) {
            return;
        }

        // Do not use if a nonce has already been defined.
        if ($this->nonce_context->hasNonce()) {
            return;
        }

        $this->nonce_context->setNonce($nonce);
    }

    private function isValidNonce(string $nonce): bool
    {
        // Same format as the generated ones: base64 of 18 bytes, without padding.
        return preg_match('/^[A-Za-z0-9+\/]{24}$/', $nonce) === 1;
    }
}
