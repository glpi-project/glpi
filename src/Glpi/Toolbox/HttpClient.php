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

use CommonGLPI;
use GLPIKey;
use Safe\Exceptions\NetworkException;
use Safe\Exceptions\UrlException;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\HttpClient as HttpClientFactory;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Component\HttpClient\Response\AsyncResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

use function Safe\dns_get_record;
use function Safe\parse_url;

final class HttpClient
{
    private HttpClientInterface $client;

    /**
     * Proxied client without private networks filtering, used for hosts that only the proxy can resolve.
     */
    private ?HttpClientInterface $unfiltered_proxy_client = null;

    /**
     * @param class-string<CommonGLPI>|null $context Configuration context.
     * @param array<string, mixed> $options Client options, see `HttpClientInterface::DEFAULT_OPTIONS`.
     * @param bool $allow_private_networks Allow usage of private networks, regardless of the configuration.
     * @param bool $bypass_proxy Prevent usage of proxy, regardless of the configuration.
     * @param bool $resolve_through_proxy Send requests to hosts that the GLPI server cannot resolve to the proxy,
     *                                    without checking them against private networks.
     *                                    Must be used only for URLs configured by administrators.
     */
    public function __construct(
        ?string $context = null,
        array $options = [],
        bool $allow_private_networks = false,
        bool $bypass_proxy = false,
        bool $resolve_through_proxy = false
    ) {
        global $CFG_GLPI;

        $client_options = $options + [
            'max_connect_duration' => 5,
        ];

        $use_proxy = $bypass_proxy !== true
            && (
                $context === null
                || \in_array($context, $CFG_GLPI['proxy_exclusions'], true) === false
            );
        $allow_private_networks = $allow_private_networks
            || (
                $context !== null
                && \in_array($context, GLPI_SERVERSIDE_URL_ALLOWED_PRIVATE_NETWORKS_CONTEXTS, true)
            );

        if ($use_proxy && !empty($CFG_GLPI['proxy_name'])) {
            $proxy_credentials = '';
            if (!empty($CFG_GLPI['proxy_user'])) {
                $proxy_credentials = sprintf(
                    '%s:%s@',
                    rawurlencode($CFG_GLPI['proxy_user']),
                    rawurlencode((new GLPIKey())->decrypt($CFG_GLPI['proxy_passwd']) ?: '')
                );
            }
            $client_options['proxy'] = sprintf(
                'http://%s%s:%d',
                $proxy_credentials,
                $CFG_GLPI['proxy_name'],
                $CFG_GLPI['proxy_port']
            );
        } elseif ($bypass_proxy) {
            $client_options['no_proxy'] = '*';
        }

        $inner_client = HttpClientFactory::create();
        if (!$allow_private_networks) {
            // The connected IP is the proxy IP when a proxy is used, so it must not be checked against private networks.
            $proxy_ips = [];
            if ($use_proxy && !empty($CFG_GLPI['proxy_name'])) {
                $proxy_host = trim($CFG_GLPI['proxy_name'], '[]');
                if (filter_var($proxy_host, FILTER_VALIDATE_IP) !== false) {
                    $proxy_ips = [$proxy_host];
                } else {
                    try {
                        $proxy_ipv6 = array_column(@dns_get_record($proxy_host, DNS_AAAA), 'ipv6');
                    } catch (NetworkException) {
                        $proxy_ipv6 = [];
                    }
                    $proxy_ips = array_merge(gethostbynamel($proxy_host) ?: [], $proxy_ipv6);
                }
                if ($resolve_through_proxy) {
                    $this->unfiltered_proxy_client = $inner_client->withOptions($client_options);
                }
            }
            $inner_client = new NoPrivateNetworkHttpClient($inner_client, null, $proxy_ips);
        }

        $this->client = $inner_client->withOptions($client_options);
    }

    /**
     * Requests an HTTP resource.
     *
     * @param Request::METHOD_* $method
     * @param string $uri
     * @param array<string, mixed> $options Client options, see `HttpClientInterface::DEFAULT_OPTIONS`.
     *
     * @return ResponseInterface
     *
     * @throws RedirectionExceptionInterface    When a 3xx error response is received (after redirect limit is reached)
     * @throws ClientExceptionInterface         When a 4xx error response is received
     * @throws ServerExceptionInterface         When a 5xx error response is received
     * @throws TransportExceptionInterface      When an unsupported option is passed
     */
    public function request(string $method, string $uri = '', array $options = []): ResponseInterface
    {
        $client = $this->client;
        if (
            $this->unfiltered_proxy_client !== null
            && !array_key_exists('proxy', $options)
            && !self::isResolvableLocally($uri)
        ) {
            // The target IP cannot be checked as the host is resolved by the proxy, which is trusted to filter it.
            // Redirections are not followed, as their target would not be checked either.
            $client = $this->unfiltered_proxy_client;
            $options['max_redirects'] = 0;
        }

        $response = $client->request($method, $uri, $options);

        if ($response->getStatusCode() >= 300 && $response->getStatusCode() < 400) {
            throw new RedirectionException($response);
        }

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500) {
            throw new ClientException($response);
        }

        if ($response->getStatusCode() >= 500 && $response->getStatusCode() < 600) {
            throw new ServerException($response);
        }

        return $response;
    }

    /**
     * Requests an HTTP resource with the GET method.
     *
     * @param string $uri
     * @param array<string, mixed> $options Client options, see `HttpClientInterface::DEFAULT_OPTIONS`.
     *
     * @return ResponseInterface
     *
     * @throws RedirectionExceptionInterface    When a 3xx error response is received (after redirect limit is reached)
     * @throws ClientExceptionInterface         When a 4xx error response is received
     * @throws ServerExceptionInterface         When a 5xx error response is received
     * @throws TransportExceptionInterface      When an unsupported option is passed
     */
    public function get(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request(Request::METHOD_GET, $uri, $options);
    }

    /**
     * Requests an HTTP resource with the POST method.
     *
     * @param string $uri
     * @param array<string, mixed> $options Client options, see `HttpClientInterface::DEFAULT_OPTIONS`.
     *
     * @return ResponseInterface
     *
     * @throws RedirectionExceptionInterface    When a 3xx error response is received (after redirect limit is reached)
     * @throws ClientExceptionInterface         When a 4xx error response is received
     * @throws ServerExceptionInterface         When a 5xx error response is received
     * @throws TransportExceptionInterface      When an unsupported option is passed
     */
    public function post(string $uri = '', array $options = []): ResponseInterface
    {
        return $this->request(Request::METHOD_POST, $uri, $options);
    }

    /**
     * Yields response chunk by chunk as it complete.
     *
     * @param ResponseInterface $response   Response created by the current HTTP client
     * @param float|null        $timeout    The idle timeout before yielding timeout chunks
     */
    public function stream(ResponseInterface $response, ?float $timeout = null): ResponseStreamInterface
    {
        // Responses of the filtering client are async responses.
        $client = $this->unfiltered_proxy_client !== null && !$response instanceof AsyncResponse
            ? $this->unfiltered_proxy_client
            : $this->client;

        return $client->stream($response, $timeout);
    }

    /**
     * Whether the URI host is an IP address or can be resolved by the GLPI server.
     * An invalid URI or an empty host (relative URI) is considered resolvable, so it stays filtered.
     */
    private static function isResolvableLocally(string $uri): bool
    {
        try {
            $host = parse_url($uri, PHP_URL_HOST);
        } catch (UrlException) {
            return true;
        }
        if (!is_string($host)) {
            return true;
        }

        $host = trim($host, '[]');
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP) !== false || gethostbynamel($host) !== false) {
            return true;
        }

        try {
            return @dns_get_record($host, DNS_AAAA) !== [];
        } catch (NetworkException) {
            return false;
        }
    }
}
