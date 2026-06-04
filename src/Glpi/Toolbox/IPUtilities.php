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

use Safe\Exceptions\NetworkException;
use function Safe\inet_ntop;
use function Safe\inet_pton;

/**
 * @final Only open for extension for use within tests.
 */
class IPUtilities
{
    /**
     * @return string[]
     */
    protected static function getTrustedReverseProxies(): array
    {
        return GLPI_TRUSTED_REVERSE_PROXIES;
    }

    /**
     * @return string[]
     */
    protected static function getTrustedReverseProxyHeaders(): array
    {
        return GLPI_REVERSE_PROXY_HEADERS;
    }

    public static function isTrustedReverseProxy(?string $ip): bool
    {
        if ($ip === null) {
            return false;
        }
        return in_array($ip, static::getTrustedReverseProxies(), true);
    }

    public static function getClientIP(): ?string
    {
        $remote_addr = $_SERVER['REMOTE_ADDR'] ?? null;
        if ($remote_addr === null) {
            return null;
        }
        if (!static::isTrustedReverseProxy($remote_addr)) {
            return $remote_addr;
        }
        $proxy_ip_headers = static::getTrustedReverseProxyHeaders();
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

    /**
     * @param string $ip The IP to check
     * @param string[] $allowed_ips Array of IPs or CIDR ranges to check against
     * @return bool
     */
    public static function isIPInList(string $ip, array $allowed_ips): bool
    {
        foreach ($allowed_ips as $allowed_ip) {
            if (str_contains($allowed_ip, '/')) {
                if (self::isCidrMatch($ip, $allowed_ip)) {
                    return true;
                }
            } elseif ($ip === $allowed_ip) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check that the given IP is in the given CIDR range
     * @param string $ip The IP to check
     * @param string $range The CIDR notation range
     * @return bool
     */
    public static function isCidrMatch(string $ip, string $range): bool
    {
        $ipv6 = str_contains($ip, ':');
        $max_mask = $ipv6 ? 128 : 32;
        [$subnet, $mask] = explode('/', $range);
        $subnet = inet_pton($subnet);
        $ip = inet_pton($ip);
        $mask = $mask === '' ? $max_mask : (int) $mask;
        $subnet = substr($subnet, 0, $mask / 8);
        $ip = substr($ip, 0, $mask / 8);
        return $subnet === $ip;
    }

    /**
     * Convert an IPv4 or IPv6 CIDR notation to a range of IPs (start and end)
     * @param string $cidr The CIDR notation to convert
     * @return array
     * @phpstan-return list{string, string}
     * @throws NetworkException
     */
    public static function cidrToRange(string $cidr): array
    {
        $ipv6 = str_contains($cidr, ':');
        $max_mask = $ipv6 ? 128 : 32;
        [$subnet, $mask] = explode('/', $cidr);
        $subnet = inet_pton($subnet);
        $mask = $mask === '' ? $max_mask : (int) $mask;
        $start = $subnet & str_repeat("\xFF", (int) ($mask / 8)) . str_repeat("\x00", (int) (($max_mask - $mask) / 8));
        $end = $subnet | str_repeat("\x00", (int) ($mask / 8)) . str_repeat("\xFF", (int) (($max_mask - $mask) / 8));
        return [inet_ntop($start), inet_ntop($end)];
    }
}
