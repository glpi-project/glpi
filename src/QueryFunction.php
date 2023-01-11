<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

/**
 *  Query function class
 **/
class QueryFunction
{
    private string $name;

    private array $params;

    private ?string $alias;

    /**
     * Create a query expression
     *
     * @param string $name Function name
     * @param array $params Function parameters
     * @param string|null $alias Function result alias
     */
    public function __construct(string $name, array $params = [], ?string $alias = null)
    {
        $this->name = $name;
        $this->params = $params;
        $this->alias = $alias;
    }

    /**
     * Query expression value
     *
     * @return string
     */
    public function getValue()
    {
        global $DB;

        $parameters = $this->params;

        $alias = $this->alias ? ' AS ' . $DB::quoteName($this->alias) : '';

        if ($this->name === 'ADDDATE') {
            $output = sprintf(
                'DATE_ADD(%s, INTERVAL %s %s)',
                $parameters[0], // Date
                $parameters[1], // Interval
                strtoupper($parameters[2]) // Unit
            );
            $output .= $alias;
            return $output;
        }

        return $this->name . '(' . implode(', ', $parameters) . ')' . $alias;
    }

    public function __toString()
    {
        return $this->getValue();
    }

    /**
     * Build a CONCAT SQL function call
     * @param array $params Function parameters. Parameters must be already quoted if needed with {@link DBmysql::quote()} or {@link DBmysql::quoteName()}
     * @param string|null $alias Function result alias
     * @return static
     */
    public static function concat(array $params, ?string $alias = null): self
    {
        return new self('CONCAT', $params, $alias);
    }

    /**
     * Build an ADDDATE SQL function call
     * Parameters must be already quoted if needed with {@link DBmysql::quote()} or {@link DBmysql::quoteName()}
     * @param string $date Date to add interval to
     * @param string $interval Interval to add
     * @param string $interval_unit Interval unit
     * @param string|null $alias Function result alias
     * @return static
     */
    public static function addDate(string $date, string $interval, string $interval_unit, ?string $alias = null): self
    {
        return new self('ADDDATE', [$date, $interval, $interval_unit], $alias);
    }
}
