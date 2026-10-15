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

use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Hides the proxy IP from the `primary_ip` reported to upper decorators.
 *
 * When requests go through a proxy, `primary_ip` is the proxy IP, which is usually private and would be
 * rejected by `NoPrivateNetworkHttpClient`, while the target host is still checked by the latter.
 * Can be replaced by the `$allowList` argument of `NoPrivateNetworkHttpClient` once Symfony >= 8.1 is used.
 */
final class ProxyAwareHttpClient implements HttpClientInterface
{
    use DecoratorTrait {
        DecoratorTrait::__construct as private decoratorConstruct;
    }

    /**
     * @param string[] $proxy_ips
     */
    public function __construct(
        HttpClientInterface $client,
        private readonly array $proxy_ips,
    ) {
        $this->decoratorConstruct($client);
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $on_progress = $options['on_progress'] ?? null;
        if ($on_progress !== null) {
            $proxy_ips = $this->proxy_ips;
            $options['on_progress'] = static function (int $dl_now, int $dl_size, array $info) use ($on_progress, $proxy_ips): void {
                if (\in_array($info['primary_ip'] ?? '', $proxy_ips, true)) {
                    $info['primary_ip'] = '';
                }
                $on_progress($dl_now, $dl_size, $info);
            };
        }

        return $this->client->request($method, $url, $options);
    }
}
