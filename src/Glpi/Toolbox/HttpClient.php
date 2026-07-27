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
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\HttpClient as HttpClientFactory;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class HttpClient
{
    private HttpClientInterface $client;

    /**
     * @param class-string<CommonGLPI>|null $context Configuration context.
     * @param array<string, mixed> $options Client options, see `HttpClientInterface::DEFAULT_OPTIONS`.
     */
    public function __construct(?string $context = null, array $options = [])
    {
        global $CFG_GLPI;

        $client_options = $options + [
            'max_connect_duration' => 5,
        ];

        $use_proxy = $context === null || \in_array($context, $CFG_GLPI['proxy_exclusions'], true) === false;
        $allow_private_networks = \in_array($context, GLPI_SERVERSIDE_URL_ALLOWED_PRIVATE_NETWORKS_CONTEXTS, true);

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
        }

        $inner_client = HttpClientFactory::create();
        if (!$allow_private_networks) {
            $inner_client = new NoPrivateNetworkHttpClient($inner_client);
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
        $response = $this->client->request($method, $uri, $options);

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
}
