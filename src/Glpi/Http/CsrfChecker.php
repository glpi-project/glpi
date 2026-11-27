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

namespace Glpi\Http;

use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

use function Safe\parse_url;

final class CsrfChecker
{
    /**
     * Check the request headers against CSRF.
     */
    public function isRequestSafeFromCSRF(Request $request): bool
    {
        $bodyless_methods = [
            Request::METHOD_GET,
            Request::METHOD_HEAD,
            Request::METHOD_OPTIONS,
            Request::METHOD_TRACE,
        ];
        if (in_array($request->getRealMethod(), $bodyless_methods)) {
            // No CSRF checks if method is not supposed to have a body.
            return true;
        }

        $sec_fetch_site = $request->headers->get('Sec-Fetch-Site');
        $origin         = $request->headers->get('Origin');
        $host           = $request->headers->get('Host');

        // The 'Sec-Fetch-Site' contains details about the request origin and is
        // a protected header so browsers will always send a legitimate value.
        // It is supported by all browsers since 2023.
        if ($sec_fetch_site !== null) {
            return $this->validateSecFetchSiteHeader($sec_fetch_site);
        }

        // We fallback to the 'Origin' header for older browsers (which is also
        // a protected header).
        if ($origin !== null && $host !== null) {
            $origin_host = parse_url($origin, PHP_URL_HOST);
            if (!is_string($origin_host) || $origin_host === "") {
                // Likely impossible, just here to make phpstan happy
                throw new RuntimeException('Failed to parse host from origin');
            }

            // Port is optional, use it only if specified
            $origin_port = parse_url($origin, PHP_URL_PORT);
            if (is_int($origin_port)) {
                return "$origin_host:$origin_port" === $host;
            }

            return $origin_host === $host;
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
