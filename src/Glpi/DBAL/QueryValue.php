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

use Glpi\Exception\Database\QueryException;

/**
 * A scalar value used in a statement.
 *
 * Renders as a `?` placeholder and carries the value to bind for it, so the value travels with
 * the SQL fragment instead of being inlined into it:
 * ```php
 * QueryFunction::dateAdd(new QueryIdentifier('date_creation'), new QueryValue(3), 'DAY')
 * ```
 *
 * Not to be confused with {@see QueryParam}, which also renders as `?` but binds *nothing*: it
 * marks the placeholders of a statement that is prepared once and executed many times.
 *
 * Note that the SQL engine does not accept a placeholder everywhere. `GROUP_CONCAT(... SEPARATOR
 * 'x')`, `CAST(x AS TYPE)` and `CONVERT(x USING charset)` require a literal, so those positions
 * cannot take a `QueryValue`.
 */
final class QueryValue implements QueryElementInterface
{
    /**
     * @param scalar|null $value Value to bind.
     */
    public function __construct(private readonly mixed $value)
    {
        if ($value !== null && !is_scalar($value)) {
            throw new QueryException(
                sprintf('A query value must be a scalar, %s given', get_debug_type($value))
            );
        }
    }

    public function getValue(): string
    {
        return '?';
    }

    public function getParams(): array
    {
        return [$this->value];
    }

    public function __toString(): string
    {
        return $this->getValue();
    }
}
