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

namespace Glpi\Api\HL\Doc;

use ArrayAccess;

/**
 * @implements ArrayAccess<string, null|string|bool|Schema>
 */
final readonly class Parameter implements ArrayAccess
{
    public const LOCATION_QUERY = 'query';
    public const LOCATION_PATH = 'path';
    public const LOCATION_HEADER = 'header';
    public const LOCATION_COOKIE = 'cookie';
    public const LOCATION_BODY = 'body';

    public function __construct(
        private string $name,
        private Schema|SchemaReference $schema,
        private string $description = '',
        private string $location = self::LOCATION_QUERY,
        private ?string $example = null,
        private bool $required = false
    ) {}

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @return Schema|SchemaReference
     */
    public function getSchema(): Schema|SchemaReference
    {
        return $this->schema;
    }

    /**
     * @return string|null
     */
    public function getExample(): ?string
    {
        return $this->example;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue(): mixed
    {
        if ($this->schema instanceof Schema) {
            return $this->schema->getDefault();
        }
        return null;
    }

    /**
     * @return bool
     */
    public function getRequired(): bool
    {
        return $this->required;
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (property_exists($this, $offset)) {
            return $this->$offset;
        }
        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        //Not supported
    }

    public function offsetUnset(mixed $offset): void
    {
        //Not supported
    }
}
