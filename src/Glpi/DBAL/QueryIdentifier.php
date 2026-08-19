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

use DBmysql;
use Glpi\Exception\Database\QueryException;

/**
 * A database identifier (table or field name) that has to be quoted.
 */
final class QueryIdentifier implements QueryElementInterface
{
    /**
     * @param string  $name  Identifier to quote. May be qualified (`table.field`), the `*`
     *                       wildcard, or already quoted.
     * @param ?string $alias Alias, quoted along with the identifier.
     */
    public function __construct(
        private readonly string $name,
        private readonly ?string $alias = null,
    ) {
        if ($name === '') {
            throw new QueryException('Cannot build an empty identifier');
        }
        if ($alias === '') {
            throw new QueryException('Cannot build an empty alias');
        }
    }

    public function getValue(): string
    {
        $sql = DBmysql::quoteName($this->name);
        if ($this->alias !== null) {
            $sql .= ' AS ' . DBmysql::quoteName($this->alias);
        }
        return $sql;
    }

    public function getParams(): array
    {
        return [];
    }

    public function __toString(): string
    {
        return $this->getValue();
    }
}
