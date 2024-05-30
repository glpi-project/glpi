<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Agent\Communication\Headers;

use ReflectionClass;
use ReflectionProperty;

class Common
{
   //Global headers
    /**
     * "Content-Type" HTTP header
     *
     * @var string
     */
    protected $content_type;

    /**
     * "Accept" HTTP header
     *
     * Must follow RFC7231 - https://tools.ietf.org/html/rfc7231#page-38
     *
     * @var string
     */
    protected $accept;

    /**
     * "Cache-Control" HTTP header
     * Required
     *
     * @var string
     */
    protected $cache_control = 'no-cache,no-store';

    /**
     * "Connection" HTTP header
     * Required
     *
     * @var string
     */
    protected $connection = 'close';

    /**
     * "Pragma" HTTP header
     * Required
     *
     * Avoid any caching done by the server
     *
     * @var string
     */
    protected $pragma = 'no-cache';

   //GLPI agent headers
    /**
     * "GLPI-Agent-ID" HTTP header
     * Required
     *
     * Plain text UUID which can be reduced in a 128 bits raw id (ex. 3a609a2e-947f-4e6a-9af9-32c024ac3944)
     *
     * @var string
     */
    protected $glpi_agent_id;

    /**
     * "GLPI-Request-ID" HTTP header
     *
     * 8 digit hexadecimal string in higher case like 2E6A9AF1
     *
     * @var string
     */
    protected $glpi_request_id;

    /**
     * "GLPI-CryptoKey-ID" HTTP header
     *
     * List of agentid separated by commas
     *
     * @var string
     */
    protected $glpi_cryptokey_id;

    /**
     * "GLPI-Proxy-ID" HTTP header
     *
     * List of agentid separated by commas
     *
     * @var string
     */
    protected $glpi_proxy_id;

    public function getRequireds(): array
    {
        return [
            'content_type',
            'pragma',
            'glpi_agent_id',
            'cache_control',
            'connection'
        ];
    }

    public function getHeadersNames(): array
    {
        return [
            'glpi_cryptokey_id' => 'GLPI-CryptoKey-ID'
        ];
    }

    /**
     * Get HTTP headers
     *
     * @param boolean $legacy Set to true to shunt required headers checks
     *
     * @return array
     */
    public function getHeaders($legacy = true): array
    {
       //parse class attributes and normalize key name
        $reflect = new ReflectionClass($this);
        $props   = $reflect->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

        $headers = [];

        foreach ($props as $prop) {
            $propname = $prop->getName();
            $headername = $this->getHeaderName($propname);
            if (!empty($this->$propname)) {
                $headers[$headername] = $this->$propname;
            } else if (in_array($propname, $this->getRequireds()) && $legacy === false) {
                throw new \RuntimeException(
                    sprintf(
                        '%1$s HTTP header is mandatory!',
                        $headername
                    )
                );
            }
        }

        return $headers;
    }

    /**
     * Get header value
     *
     * @param string $name Header name
     */
    public function getHeader($name)
    {
        $propname = strtolower(str_replace('-', '_', $name));

        return property_exists($this, $propname) ? $this->$propname : null;
    }

    /**
     * Return HTTP header name from class property name
     *
     * @param string $prop Property name
     *
     * @return string
     */
    final public function getHeaderName($prop): string
    {
        $name = $prop;

        if (isset($this->getHeadersNames()[$prop])) {
            return $this->getHeadersNames()[$prop];
        }

        $exploded = explode('_', $prop);
        foreach ($exploded as &$entry) {
            $lowered = strtolower($entry);
            switch ($lowered) {
                case 'glpi':
                case 'id':
                    $entry = strtoupper($entry);
                    break;
                default:
                    $entry = ucfirst($entry);
                    break;
            }
        }

        return implode('-', $exploded);
    }

    /**
     * Set multiple HTTP header values at once
     *
     * @param $headers Array of HTTP header name as key and value
     *
     * @return $this
     */
    public function setHeaders($headers): self
    {
        foreach ($headers as $header => $value) {
            $this->setHeader($header, $value);
        }

        return $this;
    }
    /**
     * Set HTTP header value
     *
     * @param $name HTTP header name
     * @param $value Value to set
     *
     * @return $this
     */
    public function setHeader($name, $value): self
    {
        $propname = strtolower(str_replace('-', '_', $name));
        if (property_exists($this, $propname)) {
            $this->$propname = $value;
        }
        return $this;
    }

    /**
     * Is header set
     *
     * @param string $name Property name
     *
     * @return bool
     */
    public function hasHeader($name): bool
    {
        $propname = strtolower(str_replace('-', '_', $name));
        return property_exists($this, $propname) && !empty($this->$propname);
    }
}
