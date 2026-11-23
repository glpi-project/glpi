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

namespace Glpi\DBAL;

use Stringable;

/**
 * Common contract for the SQL building blocks that may carry bound parameters.
 *
 * Implementations render themselves to a SQL fragment through {@see self::getValue()} and
 * expose the values to bind for the `?` placeholders that fragment contains through
 * {@see self::getParams()}.
 *
 * Both methods must always be used together: rendering a fragment without collecting its
 * parameters silently drops them, which shifts the positional binding of the whole statement.
 */
interface QueryElementInterface extends Stringable
{
    /**
     * SQL fragment.
     *
     * @return string
     *
     * @psalm-taint-escape sql
     */
    public function getValue(): string;

    /**
     * Values to bind, in the order their placeholders appear in {@see self::getValue()}.
     *
     * @return array<int, mixed>
     */
    public function getParams(): array;

    /**
     * SQL fragment, so that the element can be interpolated in a larger one.
     *
     * @psalm-taint-escape sql
     */
    public function __toString(): string;
}
