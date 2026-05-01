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

namespace Glpi\Toolbox;

final class IPUtilities
{
    public static function isTrustedReverseProxy(?string $ip): bool
    {
        if ($ip === null) {
            return false;
        }
        return in_array($ip, GLPI_TRUSTED_REVERSE_PROXIES, true); // @phpstan-ignore function.impossibleType
    }

    public static function getClientIP(): ?string
    {
        $remote_addr = $_SERVER['REMOTE_ADDR'] ?? null;
        if ($remote_addr === null) {
            return null;
        }
        if (!self::isTrustedReverseProxy($remote_addr)) {
            return $remote_addr;
        }
        $proxy_ip_headers = GLPI_REVERSE_PROXY_HEADERS;
        foreach ($proxy_ip_headers as $header) {
            $server_header = 'HTTP_' . str_replace('-', '_', strtoupper($header));
            if (isset($_SERVER[$server_header])) {
                if ($server_header === 'HTTP_FORWARDED') {
                    $forwarded_header = $_SERVER[$server_header];
                    $forwarded_header_parts = explode(';', $forwarded_header);
                    foreach ($forwarded_header_parts as $part) {
                        $part = trim($part);
                        if (str_starts_with($part, 'for=')) {
                            $ip = substr($part, 4);
                            // IP may be quoted and IPv6 IPs are supposed to be enclosed in square brackets.
                            return trim($ip, '"[]');
                        }
                    }
                }
                // handle standard headers (X-Forwarded-For, etc.)
                $ip_list = explode(',', $_SERVER[$server_header]);
                $ip_list = array_map('trim', $ip_list);
                // return the first IP in the list, which should be the original client IP
                return $ip_list[0];
            }
        }

        // At this point, the remote address is a trusted proxy but none of the expected headers were found, so we return the remote address as a fallback
        return $remote_addr;
    }
}
