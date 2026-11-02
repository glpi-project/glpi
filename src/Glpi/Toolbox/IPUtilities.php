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

use Glpi\Kernel\Kernel;
use LogicException;
use Safe\Exceptions\NetworkException;
use Symfony\Component\HttpFoundation\Exception\ConflictingHeadersException;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Toolbox;

use function Safe\inet_ntop;
use function Safe\inet_pton;

final class IPUtilities
{
    /**
     * Headers that can be trusted when they are sent by a trusted reverse proxy.
     */
    private const TRUSTED_HEADERS = [
        'forwarded'          => Request::HEADER_FORWARDED,
        'x-forwarded-for'    => Request::HEADER_X_FORWARDED_FOR,
        'x-forwarded-host'   => Request::HEADER_X_FORWARDED_HOST,
        'x-forwarded-proto'  => Request::HEADER_X_FORWARDED_PROTO,
        'x-forwarded-port'   => Request::HEADER_X_FORWARDED_PORT,
        'x-forwarded-prefix' => Request::HEADER_X_FORWARDED_PREFIX,
    ];

    /**
     * Configure the reverse proxies whose forwarding headers are trusted.
     *
     * @param string[] $proxies IPs or CIDR ranges of the trusted reverse proxies
     *                          (`REMOTE_ADDR` and `PRIVATE_SUBNETS` special values are supported)
     * @param string[] $headers Names of the trusted headers
     */
    public static function configureTrustedProxies(array $proxies, array $headers): void
    {
        foreach ($headers as $header) {
            if (!array_key_exists(strtolower($header), self::TRUSTED_HEADERS)) {
                trigger_error(
                    sprintf('The "%s" reverse proxy header is not supported and will be ignored.', $header),
                    E_USER_WARNING
                );
            }
        }

        $headers = array_map(strtolower(...), $headers);

        $header_set = 0;
        foreach (self::TRUSTED_HEADERS as $name => $flag) {
            if (in_array($name, $headers, true)) {
                $header_set |= $flag;
            }
        }

        Request::setTrustedProxies($proxies, $header_set);
    }

    /**
     * @deprecated 12.0.0
     */
    public static function isTrustedReverseProxy(?string $ip): bool
    {
        Toolbox::deprecated('Use `Symfony\\Component\\HttpFoundation\\Request::isFromTrustedProxy()` instead.');

        return $ip !== null && IpUtils::checkIp($ip, Request::getTrustedProxies());
    }

    /**
     * Get the IP of the client, ignoring the trusted reverse proxies.
     */
    public static function getClientIP(): ?string
    {
        /** @var Kernel $kernel */
        global $kernel;

        try {
            $ip = $kernel->getMainRequest()->getClientIp();
        } catch (ConflictingHeadersException) {
            // The trusted headers contain different client IPs.
            return null;
        }

        // Once the conflict has been reported, Symfony returns `0.0.0.0` on the next calls on the same request.
        return $ip !== '0.0.0.0' ? $ip : null;
    }

    /**
     * @param string $ip The IP to check
     * @param string[] $allowed_ips Array of IPs or CIDR ranges to check against
     * @return bool
     */
    public static function isIPInList(string $ip, array $allowed_ips): bool
    {
        return IpUtils::checkIp($ip, $allowed_ips);
    }

    /**
     * Check that the given IP is in the given CIDR range
     * @param string $ip The IP to check
     * @param string $range The CIDR notation range
     * @return bool
     *
     * @deprecated 12.0.0
     */
    public static function isCidrMatch(string $ip, string $range): bool
    {
        Toolbox::deprecated('Use `Glpi\\Toolbox\\IPUtilities::isIPInList()` instead.');

        return IpUtils::checkIp($ip, $range);
    }

    /**
     * Convert an IPv4 or IPv6 CIDR notation to a range of IPs (start and end)
     * @param string $cidr The CIDR notation to convert
     * @return array
     * @phpstan-return list{string, string}
     * @throws NetworkException
     * @throws LogicException
     */
    public static function cidrToRange(string $cidr): array
    {
        if (!str_contains($cidr, '/')) {
            throw new LogicException("Invalid CIDR notation: $cidr");
        }
        [$ip, $mask] = explode('/', $cidr);

        $mask = (int) $mask;
        $ip = inet_pton($ip);

        // IP version detection
        $ip_length = strlen($ip);

        $net_mask = '';
        $host_mask = '';

        for ($i = 0; $i < $ip_length; $i++) {
            if ($mask >= 8) {
                $net_mask .= chr(0xFF);
                $host_mask .= chr(0x00);
                $mask -= 8;
            } elseif ($mask > 0) {
                $net_bits = (0xFF << (8 - $mask)) & 0xFF;
                $net_mask .= chr($net_bits);
                $host_mask .= chr(~$net_bits & 0xFF);
                $mask = 0;
            } else {
                $net_mask .= chr(0x00);
                $host_mask .= chr(0xFF);
            }
        }

        return [inet_ntop($ip & $net_mask), inet_ntop($ip | $host_mask)];
    }
}
