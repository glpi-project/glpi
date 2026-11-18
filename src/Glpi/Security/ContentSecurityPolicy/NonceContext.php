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

final class NonceContext implements PublicService
{
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

    public function hasNonce(): bool
    {
        return $this->nonce !== null;
    }

    public function getNonce(): string
    {
        // Generate a nonce if not already set.
        return $this->nonce ??= \base64_encode(\random_bytes(18));
    }

    public function setNonce(string $nonce): void
    {
        $this->nonce = $nonce;
    }
}
