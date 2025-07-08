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

namespace Glpi\Http;

use GuzzleHttp\Psr7\ServerRequest;

class Request extends ServerRequest
{
    /** @var array<string, mixed> */
    private array $custom_attributes = [];

    /** @var array<string, mixed> */
    private array $parameters = [];

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->getAttributes());
    }

    public function getAttribute($attribute, $default = null): mixed
    {
        return $this->getAttributes()[$attribute] ?? $default;
    }

    public function getAttributes(): array
    {
        return array_merge(parent::getAttributes(), $this->custom_attributes);
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->custom_attributes[$name] = $value;
    }

    public function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function getParameter(string $name): mixed
    {
        return $this->parameters[$name];
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParsedBody()
    {
        return $this->getParameters();
    }

    public function setParameter(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }
}
