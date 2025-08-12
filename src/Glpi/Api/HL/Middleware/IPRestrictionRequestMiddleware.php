<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Api\HL\Middleware;

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Router;

use function Safe\inet_pton;

class IPRestrictionRequestMiddleware extends AbstractMiddleware implements RequestMiddlewareInterface
{
    public function process(MiddlewareInput $input, callable $next): void
    {
        $client = Router::getInstance()->getCurrentClient();
        if (!$client || isCommandLine()) {
            $next($input);
            return;
        }

        global $DB;

        $request_ip = $_SERVER['REMOTE_ADDR'];

        $result = $DB->request([
            'SELECT' => ['allowed_ips'],
            'FROM'   => 'glpi_oauthclients',
            'WHERE'  => [
                'identifier' => $client['client_id'],
            ],
        ])->current();
        $allowed_ips = $result['allowed_ips'] ?? [];

        if (empty($allowed_ips)) {
            // No IP restriction
            $next($input);
            return;
        }

        if (!$this->isIPAllowed($request_ip, $allowed_ips)) {
            // IP doesn't match the allowed IPs
            $input->response = AbstractController::getAccessDeniedErrorResponse();
            return;
        }

        // IP was explicitly allowed
        $next($input);
    }

    private function isIPAllowed(string $ip, string $allowed_ips): bool
    {
        $allowed_ip_array = array_map('trim', explode(',', $allowed_ips));
        foreach ($allowed_ip_array as $allowed_ip) {
            if (str_contains($allowed_ip, '/')) {
                if ($this->isCidrMatch($ip, $allowed_ip)) {
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
    private function isCidrMatch(string $ip, string $range): bool
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
}
