<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\DBAL;

/**
 * Collects parameters during query building for use with prepared statements.
 */
class QueryParameterBag
{
    /**
     * Collected parameter values.
     * @var array<mixed>
     */
    private array $parameters = [];

    /**
     * Add a parameter value to the bag.
     *
     * @param mixed $value The value to add.
     * @return void
     */
    public function add(mixed $value): void
    {
        $this->parameters[] = $value;
    }

    /**
     * Add multiple parameter values to the bag.
     *
     * @param array<mixed> $values The values to add.
     * @return void
     */
    public function addAll(array $values): void
    {
        foreach ($values as $value) {
            $this->add($value);
        }
    }

    /**
     * Get all collected parameters.
     *
     * @return array<mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get the number of parameters.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->parameters);
    }

    /**
     * Generate the type string for mysqli_stmt::bind_param().
     *
     * Type characters:
     * - 's' for strings
     * - 'i' for integers
     * - 'd' for doubles/floats
     * - 'b' for blobs (not used here, treated as strings)
     *
     * @return string A string of type characters (e.g., 'ssis' for string, string, int, string).
     */
    public function getTypeString(): string
    {
        $types = '';
        foreach ($this->parameters as $param) {
            $types .= match (true) {
                is_int($param) => 'i',
                is_float($param) => 'd',
                is_bool($param) => 'i', // Booleans are bound as integers
                $param === null => 's', // NULL is bound as string (will be sent as NULL)
                default => 's', // Everything else (strings, objects with __toString) as string
            };
        }
        return $types;
    }

    /**
     * Clear all collected parameters.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->parameters = [];
    }

    /**
     * Check if the bag is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }
}
