<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
 *  Query expression class
 **/
class QueryExpression
{
    private const ALWAYS_FALSE = '1 = 0';
    private const ALWAYS_TRUE = '1 = 1';

    private $expression;

    /**
     * Create a query expression
     *
     * @param string $value Query parameter value, defaults to '?'
     */
    public function __construct($expression)
    {
        if (empty($expression)) {
            throw new \RuntimeException('Cannot build an empty expression');
        }
        $this->expression = $expression;
    }

    /**
     * Query expression value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->expression;
    }

    public function __toString()
    {
        return $this->getValue();
    }

    /**
     * Create a criteria expression that will resolve to false
     * @return QueryExpression
     */
    public static function alwaysFalse()
    {
        return new self(self::ALWAYS_FALSE);
    }

    /**
     * Create a criteria expression that will resolve to true
     * @return QueryExpression
     */
    public static function alwaysTrue()
    {
        return new self(self::ALWAYS_TRUE);
    }

    /**
     * Check if this expression is always false (Represents {@link self::ALWAYS_FALSE})
     *
     * This could be used to optimize queries
     * @param $expression
     * @return bool
     */
    public static function isAlwaysFalse($expression)
    {
        return $expression === self::ALWAYS_FALSE;
    }

    /**
     * Check if this expression is always true (Represents {@link self::ALWAYS_TRUE})
     *
     * This could be used to optimize queries
     * @param $expression
     * @return bool
     */
    public static function isAlwaysTrue($expression)
    {
        return $expression === self::ALWAYS_TRUE;
    }
}
