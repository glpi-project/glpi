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

use RuntimeException;

/**
 *  Query expression class
 **/
class QueryExpression
{
    private string $expression;

    private ?string $alias;

    /**
     * Create a query expression
     *
     * @param string $expression The query expression
     * @param ?string $alias     The query expression alias
     */
    public function __construct($expression, ?string $alias = null)
    {
        if ($expression === null || $expression === '' || $expression === false) {
            throw new RuntimeException('Cannot build an empty expression');
        }
        $this->expression = $expression;
        $this->alias = $alias;
    }

    /**
     * Query expression value
     *
     * @return string
     *
     * @psalm-taint-escape sql
     */
    public function getValue()
    {
        global $DB;
        $sql = $this->expression;
        if (!empty($this->alias)) {
            $sql .= ' AS ' . $DB::quoteName($this->alias);
        }
        return $sql;
    }

    public function __toString()
    {
        return $this->getValue();
    }
}
