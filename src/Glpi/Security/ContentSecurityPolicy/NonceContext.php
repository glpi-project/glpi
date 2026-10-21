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

namespace Glpi\Security\ContentSecurityPolicy;

use Glpi\DependencyInjection\PublicService;
use Glpi\Kernel\Kernel;
use Symfony\Component\HttpFoundation\Request;

use function Safe\preg_match;

final class NonceContext implements PublicService
{
    private const string REQUEST_NONCE_HEADER_NAME = 'X-Glpi-Csp-Nonce';

    private ?string $nonce = null;

    /**
     * Allow access to the service instance outside dependency injection.
     * Needed for our code that is not yet plugged into DI.
     */
    public static function getServiceInstance(): self
    {
        /** @var Kernel $kernel */
        global $kernel;

        $service = $kernel->getContainer()->get(self::class);
        if (!$service instanceof self) {
            throw new \RuntimeException('Service not found: ' . self::class);
        }

        return $service;
    }

    public function getNonce(): string
    {
        // Generate a nonce if not already set.
        return $this->nonce ??= \base64_encode(\random_bytes(18));
    }

    /**
     * Reuse the nonce of the page that issued the request, so the HTML returned
     * to an AJAX call carries a nonce that matches the page CSP.
     */
    public function setNonceFromRequest(Request $request): void
    {
        $nonce = $request->headers->get(self::REQUEST_NONCE_HEADER_NAME);
        if ($nonce === null || !$this->isValidNonce($nonce)) {
            return;
        }
        if ($this->nonce !== null && $this->nonce !== $nonce) {
            // A nonce has already been read and possibly sent in the output.
            return;
        }

        $this->nonce = $nonce;
    }

    private function isValidNonce(string $nonce): bool
    {
        // Same format as the generated ones: base64 of 18 bytes, without padding.
        return preg_match('/^[A-Za-z0-9+\/]{24}$/', $nonce) === 1;
    }
}
