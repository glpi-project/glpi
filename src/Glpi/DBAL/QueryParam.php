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

namespace Glpi\DBAL;

class QueryParam
{
    /**
     * The bound parameter value.
     */
    private mixed $value = null;

    /**
     * Whether a value has been bound.
     */
    private bool $hasValue = false;

    /**
     * Create a query parameter placeholder.
     *
     * @param mixed $value Optional value to bind to this parameter.
     *                     If provided, the value will be used in prepared statement binding.
     */
    public function __construct(mixed $value = null)
    {
        if (func_num_args() > 0) {
            $this->value = $value;
            $this->hasValue = true;
        }
    }

    /**
     * Query parameter placeholder value (always returns '?').
     *
     * @return string
     */
    public function getValue(): string
    {
        return '?';
    }

    /**
     * Get the bound parameter value.
     *
     * @return mixed The bound value, or null if no value was bound.
     */
    public function getBoundValue(): mixed
    {
        return $this->value;
    }

    /**
     * Check if a value has been bound to this parameter.
     *
     * @return bool True if a value was provided in the constructor.
     */
    public function hasValue(): bool
    {
        return $this->hasValue;
    }

    public function __toString(): string
    {
        return $this->getValue();
    }
}
