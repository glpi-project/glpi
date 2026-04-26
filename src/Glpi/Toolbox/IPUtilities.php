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

use function Safe\inet_pton;

final class IPUtilities
{
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
