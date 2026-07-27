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

use RuntimeException;

/**
 *  Query expression class
 **/
class QueryExpression
{
    private string $expression;

    private ?string $alias;
    /** @var array<int, mixed> */
    private array $params = [];


    /**
     * Create a query expression
     *
     * @param string|QueryExpression $expression The query expression
     * @param ?string $alias     The query expression alias
     * @param array<int, mixed> $values    The query expression values
     */
    public function __construct(string|QueryExpression $expression, ?string $alias = null, array $values = [])
    {
        if ($expression === '') {
            throw new RuntimeException('Cannot build an empty expression');
        }
        $this->alias = $alias;

        if ($expression instanceof QueryExpression) {
            $this->expression = $expression->getValue();
            $values = array_merge($expression->getParams(), $values);
        } else {
            $this->expression = $expression;
        }
        $this->setParams($values);
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

    /**
     * @return array<int, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array<int, mixed> $params
     */
    public function setParams(array $params): static
    {
        $this->params = $params;
        return $this;
    }
}
